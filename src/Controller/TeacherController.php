<?php

namespace App\Controller;

use App\Entity\Test;
use App\Entity\Question;
use App\Entity\Answer;
use App\Repository\AttemptRepository;
use App\Repository\TestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher')]
#[IsGranted('ROLE_TEACHER')]
class TeacherController extends AbstractController
{
    #[Route('/', name: 'app_teacher_panel')]
    public function dashboard(TestRepository $testRepository): Response
    {
        $tests = $testRepository->findBy(['created_by' => $this->getUser()], ['created_at' => 'DESC']);

        return $this->render('teacher/dashboard.html.twig', [
            'tests' => $tests,
        ]);
    }

    #[Route('/students', name: 'app_teacher_students')]
    public function students(UserRepository $userRepository, AttemptRepository $attemptRepository): Response
    {
        $students = $userRepository->findBy(['role' => 'student']);

        $studentData = [];
        foreach ($students as $student) {
            $attempts = $attemptRepository->count(['user' => $student]);
            $lastAttempt = $attemptRepository->findOneBy(['user' => $student], ['end_time' => 'DESC']);
            $studentData[] = [
                'student' => $student,
                'attempts' => $attempts,
                'lastScore' => $lastAttempt ? round(($lastAttempt->getScore() / $lastAttempt->getMaxScore()) * 100) : null,
            ];
        }

        return $this->render('teacher/students.html.twig', [
            'studentData' => $studentData,
        ]);
    }

    #[Route('/statistics', name: 'app_teacher_statistics')]
    public function statistics(UserRepository $userRepository, TestRepository $testRepository, AttemptRepository $attemptRepository): Response
    {
        $totalStudents = $userRepository->count(['role' => 'student']);
        $totalTeachers = $userRepository->count(['role' => 'teacher']);
        $totalTests = $testRepository->count([]);
        $totalAttempts = $attemptRepository->count([]);

        $completedAttempts = $attemptRepository->findBy(['status' => 'completed']);
        $avgScore = 0;
        if (count($completedAttempts) > 0) {
            $sumPercent = 0;
            foreach ($completedAttempts as $attempt) {
                if ($attempt->getMaxScore() > 0) {
                    $sumPercent += ($attempt->getScore() / $attempt->getMaxScore()) * 100;
                }
            }
            $avgScore = round($sumPercent / count($completedAttempts));
        }

        $testsStats = [];
        $tests = $testRepository->findAll();
        foreach ($tests as $test) {
            $attempts = $attemptRepository->findBy(['test' => $test, 'status' => 'completed']);
            $countAttempts = count($attempts);
            $avgTestScore = 0;
            if ($countAttempts > 0) {
                $sum = 0;
                foreach ($attempts as $a) {
                    if ($a->getMaxScore() > 0) {
                        $sum += ($a->getScore() / $a->getMaxScore()) * 100;
                    }
                }
                $avgTestScore = round($sum / $countAttempts);
            }
            $testsStats[] = [
                'test' => $test,
                'attempts' => $countAttempts,
                'avgScore' => $avgTestScore,
            ];
        }

        return $this->render('teacher/statistics.html.twig', [
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalTests' => $totalTests,
            'totalAttempts' => $totalAttempts,
            'avgScore' => $avgScore,
            'testsStats' => $testsStats,
        ]);
    }

    #[Route('/test/new', name: 'app_teacher_test_new', methods: ['GET', 'POST'])]
    public function newTest(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $test = new Test();
            $test->setTitle($data['title']);
            $test->setDescription($data['description'] ?? null);
            $test->setSubject($data['subject'] ?? null);
            $test->setTimeLimit($data['timeLimit'] ?? null);
            $test->setCreatedBy($this->getUser());

            // Обработка выбранных групп
            $targetGroups = $request->request->all('targetGroups');
            if (in_array('all', $targetGroups)) {
                $targetGroups = ['all'];
            }
            $test->setTargetGroups($targetGroups);

            $questionsData = $data['questions'] ?? [];

            foreach ($questionsData as $qIndex => $qData) {
                if (empty($qData['text'])) continue;

                $question = new Question();
                $question->setQuestionText($qData['text']);
                $question->setQuestionType($qData['type'] ?? 'single');
                $question->setPoints($qData['points'] ?? 1);
                $question->setOrderNum($qIndex + 1);

                $answersData = $qData['answers'] ?? [];
                $correctAnswers = $qData['correct'] ?? [];

                foreach ($answersData as $aIndex => $aText) {
                    if (empty($aText)) continue;

                    $answer = new Answer();
                    $answer->setAnswerText($aText);
                    $answer->setIsCorrect(in_array($aIndex, $correctAnswers));
                    $answer->setOrderNum($aIndex + 1);

                    $question->addAnswer($answer);
                }

                $test->addQuestion($question);
            }

            $entityManager->persist($test);
            $entityManager->flush();

            $this->addFlash('success', 'Тест успешно создан!');
            return $this->redirectToRoute('app_teacher_panel');
        }

        return $this->render('teacher/test_form.html.twig', [
            'test' => null,
        ]);
    }

    #[Route('/test/{id}/edit', name: 'app_teacher_test_edit', methods: ['GET', 'POST'])]
    public function editTest(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        if ($test->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $test->setTitle($data['title']);
            $test->setDescription($data['description'] ?? null);
            $test->setSubject($data['subject'] ?? null);
            $test->setTimeLimit($data['timeLimit'] ?? null);

            // Обработка выбранных групп
            $targetGroups = $request->request->all('targetGroups');
            if (in_array('all', $targetGroups)) {
                $targetGroups = ['all'];
            }
            $test->setTargetGroups($targetGroups);

            // Удаляем старые вопросы и ответы
            foreach ($test->getQuestions() as $oldQuestion) {
                $entityManager->remove($oldQuestion);
            }
            $test->getQuestions()->clear();

            $questionsData = $data['questions'] ?? [];

            foreach ($questionsData as $qIndex => $qData) {
                if (empty($qData['text'])) continue;

                $question = new Question();
                $question->setQuestionText($qData['text']);
                $question->setQuestionType($qData['type'] ?? 'single');
                $question->setPoints($qData['points'] ?? 1);
                $question->setOrderNum($qIndex + 1);

                $answersData = $qData['answers'] ?? [];
                $correctAnswers = $qData['correct'] ?? [];

                foreach ($answersData as $aIndex => $aText) {
                    if (empty($aText)) continue;

                    $answer = new Answer();
                    $answer->setAnswerText($aText);
                    $answer->setIsCorrect(in_array($aIndex, $correctAnswers));
                    $answer->setOrderNum($aIndex + 1);

                    $question->addAnswer($answer);
                }

                $test->addQuestion($question);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Тест обновлён!');
            return $this->redirectToRoute('app_teacher_panel');
        }

        return $this->render('teacher/test_form.html.twig', [
            'test' => $test,
        ]);
    }

    #[Route('/test/{id}/delete', name: 'app_teacher_test_delete', methods: ['POST'])]
    public function deleteTest(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        if ($test->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$test->getId(), $request->request->get('_token'))) {
            $entityManager->remove($test);
            $entityManager->flush();
            $this->addFlash('success', 'Тест удалён.');
        }

        return $this->redirectToRoute('app_teacher_panel');
    }
}