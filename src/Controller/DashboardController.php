<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        // Фиктивные данные для демонстрации
        $stats = [
            'completedTests' => 5,
            'averageScore' => 78,
            'lastScore' => 85,
            'createdTests' => 12,
            'activeStudents' => 45,
            'totalAttempts' => 120
        ];

        $recentResults = [
            [
                'test' => ['id' => 1, 'title' => 'Основы PHP'],
                'score' => 85,
                'correctAnswers' => 17,
                'totalQuestions' => 20,
                'completedAt' => new \DateTime('-1 day')
            ],
            [
                'test' => ['id' => 2, 'title' => 'Базы данных'],
                'score' => 72,
                'correctAnswers' => 14,
                'totalQuestions' => 20,
                'completedAt' => new \DateTime('-3 days')
            ]
        ];

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recentResults' => $recentResults
        ]);
    }
}