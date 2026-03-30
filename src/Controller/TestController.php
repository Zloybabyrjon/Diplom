<?php

namespace App\Controller;

use App\Entity\Attempt;
use App\Entity\AttemptAnswer;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Repository\AttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tests')]
#[IsGranted('ROLE_STUDENT')]
class TestController extends AbstractController
{
    #[Route('/', name: 'app_test_list')]
    public function list(TestRepository $testRepository, AttemptRepository $attemptRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $allTests = $testRepository->findAll();

        $testsData = [];
        foreach ($allTests as $test) {
            // Проверяем, пройден ли тест пользователем
            $completedAttempt = $attemptRepository->findOneBy([
                'user' => $user,
                'test' => $test,
                'status' => 'completed'
            ]);
            $completed = $completedAttempt !== null;

            // Проверяем доступность теста по группам
            $targetGroups = $test->getTargetGroups();
            $accessible = true;
            if (!empty($targetGroups) && !in_array('all', $targetGroups)) {
                $accessible = in_array($user->getGroupName(), $targetGroups);
            }

            if ($accessible) {
                $testsData[] = [
                    'test' => $test,
                    'completed' => $completed,
                    'score' => $completedAttempt ? round(($completedAttempt->getScore() / $completedAttempt->getMaxScore()) * 100) : null,
                ];
            }
        }

        return $this->render('student/test_list.html.twig', [
            'testsData' => $testsData,
        ]);
    }

    #[Route('/{id}/start', name: 'app_test_start')]
    public function start(Test $test, EntityManagerInterface $entityManager): Response
    {
        // Проверяем, есть ли уже незавершённая попытка
        $existingAttempt = $entityManager->getRepository(Attempt::class)->findOneBy([
            'user' => $this->getUser(),
            'test' => $test,
            'status' => 'in_progress',
        ]);

        if ($existingAttempt) {
            return $this->redirectToRoute('app_test_take', ['id' => $test->getId(), 'attempt' => $existingAttempt->getId()]);
        }

        $attempt = new Attempt();
        $attempt->setUser($this->getUser());
        $attempt->setTest($test);
        $attempt->setMaxScore($this->calculateMaxScore($test));

        $entityManager->persist($attempt);
        $entityManager->flush();

        return $this->redirectToRoute('app_test_take', ['id' => $test->getId(), 'attempt' => $attempt->getId()]);
    }

    #[Route('/{id}/take', name: 'app_test_take')]
    public function take(Test $test, Request $request, EntityManagerInterface $entityManager): Response
    {
        $attemptId = $request->query->get('attempt');
        $attempt = $entityManager->getRepository(Attempt::class)->find($attemptId);

        if (!$attempt || $attempt->getUser() !== $this->getUser() || $attempt->getStatus() !== 'in_progress') {
            throw $this->createAccessDeniedException();
        }

        $questions = $test->getQuestions();

        return $this->render('student/test_taking.html.twig', [
            'test' => $test,
            'attempt' => $attempt,
            'questions' => $questions,
        ]);
    }

    #[Route('/{id}/submit', name: 'app_test_submit', methods: ['POST'])]
    public function submit(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        $attemptId = $request->request->get('attempt_id');
        $attempt = $entityManager->getRepository(Attempt::class)->find($attemptId);

        if (!$attempt || $attempt->getUser() !== $this->getUser() || $attempt->getStatus() !== 'in_progress') {
            throw $this->createAccessDeniedException();
        }

        $answers = $request->request->all('answers');
        $questions = $test->getQuestions();
        $score = 0;
        $maxScore = $attempt->getMaxScore();

        foreach ($questions as $qIndex => $question) {
            $userAnswerIndexes = $answers[$qIndex] ?? [];
            if (!is_array($userAnswerIndexes)) {
                $userAnswerIndexes = [$userAnswerIndexes];
            }

            $correctAnswerIds = [];
            foreach ($question->getAnswers() as $aIndex => $answer) {
                if ($answer->isIsCorrect()) {
                    $correctAnswerIds[] = $answer->getId();
                }
            }

            $selectedAnswerIds = [];
            foreach ($userAnswerIndexes as $aIndex) {
                $answer = $question->getAnswers()[$aIndex] ?? null;
                if ($answer) {
                    $selectedAnswerIds[] = $answer->getId();
                }
            }

            $isCorrect = (count($correctAnswerIds) === count($selectedAnswerIds)) && !array_diff($correctAnswerIds, $selectedAnswerIds);

            foreach ($selectedAnswerIds as $answerId) {
                $attemptAnswer = new AttemptAnswer();
                $attemptAnswer->setAttempt($attempt);
                $attemptAnswer->setQuestion($question);
                $answer = $entityManager->getRepository(\App\Entity\Answer::class)->find($answerId);
                $attemptAnswer->setAnswer($answer);
                $attemptAnswer->setIsCorrect($isCorrect);
                $entityManager->persist($attemptAnswer);
            }

            if ($isCorrect) {
                $score += $question->getPoints();
            }
        }

        $percentage = ($maxScore > 0) ? ($score / $maxScore) * 100 : 0;
        if ($percentage >= 90) $grade = '5 (отлично)';
        elseif ($percentage >= 75) $grade = '4 (хорошо)';
        elseif ($percentage >= 60) $grade = '3 (удовлетворительно)';
        else $grade = '2 (неудовлетворительно)';

        $attempt->setScore($score);
        $attempt->setGrade($grade);
        $attempt->setEndTime(new \DateTime());
        $attempt->setStatus('completed');

        $entityManager->flush();

        return $this->redirectToRoute('app_test_result', ['id' => $attempt->getId()]);
    }

    #[Route('/result/{id}', name: 'app_test_result')]
    public function result(Attempt $attempt): Response
    {
        if ($attempt->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $test = $attempt->getTest();
        $questions = $test->getQuestions();
        $userAnswers = $attempt->getAttemptAnswers();

        $questionsData = [];
        foreach ($questions as $question) {
            $correctAnswers = [];
            foreach ($question->getAnswers() as $answer) {
                if ($answer->isIsCorrect()) {
                    $correctAnswers[] = ['text' => $answer->getAnswerText()];
                }
            }

            $userAnswersForQuestion = $userAnswers->filter(fn($ua) => $ua->getQuestion() === $question);
            $userAnswersData = [];
            foreach ($userAnswersForQuestion as $ua) {
                $userAnswersData[] = [
                    'text' => $ua->getAnswer()->getAnswerText(),
                    'isCorrect' => $ua->isIsCorrect(),
                ];
            }

            $questionsData[] = [
                'text' => $question->getQuestionText(),
                'isCorrect' => $userAnswersForQuestion->count() > 0 && $userAnswersForQuestion->first()->isIsCorrect(),
                'userAnswers' => $userAnswersData,
                'correctAnswers' => $correctAnswers,
            ];
        }

        return $this->render('student/test_result.html.twig', [
            'test' => ['title' => $test->getTitle()],
            'score' => round(($attempt->getScore() / $attempt->getMaxScore()) * 100),
            'scoreClass' => $this->getScoreClass($attempt->getScore(), $attempt->getMaxScore()),
            'grade' => $attempt->getGrade(),
            'gradeColor' => $this->getGradeColor($attempt->getGrade()),
            'gradeMessage' => $this->getGradeMessage($attempt->getGrade()),
            'correctAnswers' => $attempt->getScore(),
            'totalQuestions' => $questions->count(),
            'timeSpent' => $this->formatTime($attempt->getStartTime(), $attempt->getEndTime()),
            'questions' => $questionsData,
        ]);
    }

    private function calculateMaxScore(Test $test): int
    {
        $max = 0;
        foreach ($test->getQuestions() as $question) {
            $max += $question->getPoints();
        }
        return $max;
    }

    private function getScoreClass(int $score, int $max): string
    {
        $percent = ($max > 0) ? ($score / $max) * 100 : 0;
        if ($percent >= 85) return 'score-excellent';
        if ($percent >= 70) return 'score-good';
        if ($percent >= 50) return 'score-medium';
        return 'score-poor';
    }

    private function getGradeColor(string $grade): string
    {
        if (str_contains($grade, '5')) return 'success';
        if (str_contains($grade, '4')) return 'info';
        if (str_contains($grade, '3')) return 'warning';
        return 'danger';
    }

    private function getGradeMessage(string $grade): string
    {
        if (str_contains($grade, '5')) return 'Отлично! Так держать!';
        if (str_contains($grade, '4')) return 'Хорошо, но есть куда расти.';
        if (str_contains($grade, '3')) return 'Удовлетворительно. Стоит повторить материал.';
        return 'К сожалению, тест не пройден. Попробуйте ещё раз.';
    }

    private function formatTime(\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$end) return '—';
        $diff = $end->getTimestamp() - $start->getTimestamp();
        $minutes = floor($diff / 60);
        $seconds = $diff % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}