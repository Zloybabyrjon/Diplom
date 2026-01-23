<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeacherController extends AbstractController
{
    #[Route('/teacher', name: 'app_teacher_panel')]
    public function dashboard(): Response
    {
        // Временно закомментируйте проверку для разработки
        // $this->denyAccessUnlessGranted('ROLE_TEACHER');

        // Фиктивные данные для демонстрации
        $tests = [
            [
                'id' => 1,
                'title' => 'Основы PHP',
                'description' => 'Тест по основам языка PHP для начинающих',
                'subject' => 'Программирование',
                'group' => 'ПИ-20-1',
                'questionCount' => 20,
                'createdAt' => new \DateTime('-5 days')
            ],
            [
                'id' => 2,
                'title' => 'Базы данных SQL',
                'description' => 'Тест по основам работы с базами данных',
                'subject' => 'Базы данных',
                'group' => 'all',
                'questionCount' => 15,
                'createdAt' => new \DateTime('-3 days')
            ],
            [
                'id' => 3,
                'title' => 'Алгоритмы и структуры данных',
                'description' => 'Проверка знаний основных алгоритмов',
                'subject' => 'Алгоритмы',
                'group' => 'ИВТ-20-1',
                'questionCount' => 25,
                'createdAt' => new \DateTime('-1 day')
            ]
        ];

        return $this->render('teacher/dashboard.html.twig', [
            'tests' => $tests
        ]);
    }
}