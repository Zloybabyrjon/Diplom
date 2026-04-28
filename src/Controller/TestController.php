<?php

namespace App\Controller;

use App\Entity\Attempt;
use App\Entity\AttemptAnswer;
use App\Entity\Test;
use App\Repository\AttemptRepository;
use App\Repository\TestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tests')]
class TestController extends AbstractController
{
    #[Route('/', name: 'app_test_list')]
    #[IsGranted('ROLE_STUDENT')]
    public function list(TestRepository $testRepository, AttemptRepository $attemptRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $allTests = $testRepository->findAll();
        $testsData = [];

        foreach ($allTests as $test) {
            $targetGroups = $test->getTargetGroups();
            $accessible = true;
            if (!empty($targetGroups) && !in_array('all', $targetGroups)) {
                $accessible = in_array($user->getGroupName(), $targetGroups);
            }
            if (!$accessible) continue;

            $completedAttempt = $attemptRepository->findOneBy([
                'user' => $user,
                'test' => $test,
                'status' => 'completed'
            ]);

            $testsData[] = [
                'test' => $test,
                'completed' => $completedAttempt !== null,
                'attemptId' => $completedAttempt ? $completedAttempt->getId() : null,
                'score' => $completedAttempt ? round(($completedAttempt->getScore() / $completedAttempt->getMaxScore()) * 100) : null,
            ];
        }

        return $this->render('student/test_list.html.twig', ['testsData' => $testsData]);
    }

    #[Route('/{id}/take', name: 'app_test_take')]
#[IsGranted('ROLE_STUDENT')]
public function take(Test $test, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    if (!$user instanceof \App\Entity\User) {
        return $this->redirectToRoute('app_login');
    }

    $attempt = $entityManager->getRepository(Attempt::class)->findOneBy([
        'user' => $user,
        'test' => $test,
        'status' => 'in_progress',
    ]);
    if (!$attempt) {
        $attempt = new Attempt();
        $attempt->setUser($user);
        $attempt->setTest($test);
        $attempt->setMaxScore($this->calculateMaxScore($test));
        $entityManager->persist($attempt);
        $entityManager->flush();
    }

    // Расчёт оставшегося времени в секундах
    $timeLimitMinutes = $test->getTimeLimit() ?? 60;
    $startTimestamp = $attempt->getStartTime()->getTimestamp();
    $endTimestamp = $startTimestamp + ($timeLimitMinutes * 60);
    $remainingSeconds = max(0, $endTimestamp - time());

    $questions = $test->getQuestions();
    return $this->render('student/test_taking.html.twig', [
        'test' => $test,
        'attempt' => $attempt,
        'questions' => $questions,
        'remainingSeconds' => $remainingSeconds,
    ]);
}

    #[Route('/{id}/submit', name: 'app_test_submit', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
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
        $correctCount = 0;

        foreach ($questions as $qIndex => $question) {
            $userAnswerIndexes = $answers[$qIndex] ?? [];
            if (!is_array($userAnswerIndexes)) $userAnswerIndexes = [$userAnswerIndexes];

            $correctAnswerIds = [];
            foreach ($question->getAnswers() as $aIndex => $answer) {
                if ($answer->isIsCorrect()) $correctAnswerIds[] = $answer->getId();
            }

            $selectedAnswerIds = [];
            foreach ($userAnswerIndexes as $aIndex) {
                $answer = $question->getAnswers()[$aIndex] ?? null;
                if ($answer) $selectedAnswerIds[] = $answer->getId();
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
                $correctCount++;
            }
        }

        $percentage = ($maxScore > 0) ? ($score / $maxScore) * 100 : 0;
        $grade = match(true) {
            $percentage >= 90 => '5 (отлично)',
            $percentage >= 75 => '4 (хорошо)',
            $percentage >= 60 => '3 (удовлетворительно)',
            default => '2 (неудовлетворительно)',
        };

        $attempt->setScore($score);
        $attempt->setGrade($grade);
        $attempt->setEndTime(new \DateTime());
        $attempt->setStatus('completed');
        $entityManager->flush();

        return $this->redirectToRoute('app_test_result', ['id' => $attempt->getId()]);
    }

    #[Route('/result/{id}', name: 'app_test_result')]
    public function result(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $attempt = $entityManager->getRepository(Attempt::class)->find($id);
        if (!$attempt) {
            throw $this->createNotFoundException('Попытка не найдена');
        }
        if ($attempt->getUser() !== $user && !$this->isGranted('ROLE_TEACHER')) {
            throw $this->createAccessDeniedException();
        }

        $test = $attempt->getTest();
        $userAnswers = $attempt->getAttemptAnswers();
        $questions = $test->getQuestions();

        $questionsData = [];
        $correctCount = 0;
        foreach ($questions as $question) {
            $correctAnswers = [];
            foreach ($question->getAnswers() as $answer) {
                if ($answer->isIsCorrect()) $correctAnswers[] = ['text' => $answer->getAnswerText()];
            }

            $userAnswersForQuestion = $userAnswers->filter(fn($ua) => $ua->getQuestion() === $question);
            $userAnswersData = [];
            foreach ($userAnswersForQuestion as $ua) {
                $userAnswersData[] = [
                    'text' => $ua->getAnswer()->getAnswerText(),
                    'isCorrect' => $ua->isIsCorrect(),
                ];
            }

            $isQuestionCorrect = $userAnswersForQuestion->count() > 0 && $userAnswersForQuestion->first()->isIsCorrect();
            if ($isQuestionCorrect) $correctCount++;

            $questionsData[] = [
                'text' => $question->getQuestionText(),
                'isCorrect' => $isQuestionCorrect,
                'userAnswers' => $userAnswersData,
                'correctAnswers' => $correctAnswers,
            ];
        }

        $timeSpent = $this->formatTime($attempt->getStartTime(), $attempt->getEndTime());

        return $this->render('student/test_result.html.twig', [
            'test' => ['title' => $test->getTitle()],
            'score' => round(($attempt->getScore() / $attempt->getMaxScore()) * 100),
            'scoreClass' => $this->getScoreClass($attempt->getScore(), $attempt->getMaxScore()),
            'grade' => $attempt->getGrade(),
            'gradeColor' => $this->getGradeColor($attempt->getGrade()),
            'gradeMessage' => $this->getGradeMessage($attempt->getGrade()),
            'correctAnswers' => $correctCount,
            'totalQuestions' => $questions->count(),
            'timeSpent' => $timeSpent,
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
        if ($diff < 0) $diff = 0;
        $minutes = floor($diff / 60);
        $seconds = $diff % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}