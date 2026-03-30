<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AttemptRepository;
use App\Repository\TestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(AttemptRepository $attemptRepository, TestRepository $testRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
    return $this->redirectToRoute('app_login');
}

        if ($user->getRole() === 'teacher') {
            $stats = [
                'createdTests' => $testRepository->count(['created_by' => $user]),
                'activeStudents' => 0, // можно посчитать отдельно
                'totalAttempts' => 0,
            ];
            $recentResults = [];
        } else {
            $attempts = $attemptRepository->findBy(['user' => $user, 'status' => 'completed'], ['end_time' => 'DESC'], 5);
            $completedTests = count($attempts);
            $averageScore = $completedTests > 0 ? array_sum(array_map(fn($a) => $a->getScore(), $attempts)) / $completedTests : 0;
            $lastScore = $attempts[0] ?? null;

            $stats = [
                'completedTests' => $completedTests,
                'averageScore' => round($averageScore),
                'lastScore' => $lastScore ? round(($lastScore->getScore() / $lastScore->getMaxScore()) * 100) : 0,
            ];

            $recentResults = [];
            foreach ($attempts as $attempt) {
                $recentResults[] = [
                    'test' => ['id' => $attempt->getTest()->getId(), 'title' => $attempt->getTest()->getTitle()],
                    'score' => round(($attempt->getScore() / $attempt->getMaxScore()) * 100),
                    'correctAnswers' => $attempt->getScore(),
                    'totalQuestions' => $attempt->getTest()->getQuestions()->count(),
                    'completedAt' => $attempt->getEndTime(),
                ];
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recentResults' => $recentResults ?? [],
        ]);
    }
}