<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/tests', name: 'app_test_list')]
    public function list(): Response
    {
        // Фиктивные данные для демонстрации
        $tests = [
            [
                'id' => 1,
                'title' => 'Основы PHP',
                'subject' => 'Программирование',
                'description' => 'Тест по основам языка PHP',
                'questionsCount' => 10,
                'timeLimit' => 30,
                'completed' => false,
                'score' => null
            ],
            [
                'id' => 2,
                'title' => 'Базы данных',
                'subject' => 'SQL',
                'description' => 'Тест по работе с базами данных',
                'questionsCount' => 15,
                'timeLimit' => 45,
                'completed' => true,
                'score' => 85
            ],
            [
                'id' => 3,
                'title' => 'HTML/CSS',
                'subject' => 'Веб-разработка',
                'description' => 'Основы верстки веб-страниц',
                'questionsCount' => 12,
                'timeLimit' => 25,
                'completed' => false,
                'score' => null
            ]
        ];

        return $this->render('student/test_list.html.twig', [
            'tests' => $tests
        ]);
    }

    #[Route('/test/{id}', name: 'app_test_take')]
    public function take(int $id): Response
    {
        // Фиктивные данные для демонстрации
        $test = [
            'id' => $id,
            'title' => 'Основы PHP',
            'subject' => 'Программирование',
            'questions' => [
                [
                    'text' => 'Что означает аббревиатура PHP?',
                    'type' => 'single',
                    'answers' => [
                        ['text' => 'Personal Home Page', 'correct' => true],
                        ['text' => 'Programming Hypertext Processor'],
                        ['text' => 'Public Hosting Platform'],
                        ['text' => 'Private Host Protocol']
                    ]
                ],
                [
                    'text' => 'Какие из перечисленных типов данных существуют в PHP?',
                    'type' => 'multiple',
                    'answers' => [
                        ['text' => 'String', 'correct' => true],
                        ['text' => 'Integer', 'correct' => true],
                        ['text' => 'Float', 'correct' => true],
                        ['text' => 'Boolean', 'correct' => true],
                        ['text' => 'Array', 'correct' => true]
                    ]
                ],
                [
                    'text' => 'Какой оператор используется для конкатенации строк в PHP?',
                    'type' => 'single',
                    'answers' => [
                        ['text' => '+'],
                        ['text' => '.', 'correct' => true],
                        ['text' => '&'],
                        ['text' => '|']
                    ]
                ]
            ]
        ];

        return $this->render('student/test_taking.html.twig', [
            'test' => $test
        ]);
    }

    #[Route('/test/{id}/result', name: 'app_test_result')]
    public function result(int $id): Response
    {
        // Фиктивные данные для демонстрации
        $test = [
            'id' => $id,
            'title' => 'Основы PHP'
        ];

        $questions = [
            [
                'text' => 'Что означает аббревиатура PHP?',
                'isCorrect' => true,
                'userAnswers' => [
                    ['text' => 'Personal Home Page', 'isCorrect' => true]
                ],
                'correctAnswers' => [
                    ['text' => 'Personal Home Page']
                ]
            ],
            [
                'text' => 'Какие из перечисленных типов данных существуют в PHP?',
                'isCorrect' => false,
                'userAnswers' => [
                    ['text' => 'String', 'isCorrect' => true],
                    ['text' => 'Integer', 'isCorrect' => true],
                    ['text' => 'Float', 'isCorrect' => true]
                ],
                'correctAnswers' => [
                    ['text' => 'String'],
                    ['text' => 'Integer'],
                    ['text' => 'Float'],
                    ['text' => 'Boolean'],
                    ['text' => 'Array']
                ]
            ],
            [
                'text' => 'Какой оператор используется для конкатенации строк в PHP?',
                'isCorrect' => true,
                'userAnswers' => [
                    ['text' => '.', 'isCorrect' => true]
                ],
                'correctAnswers' => [
                    ['text' => '.']
                ]
            ]
        ];

        return $this->render('student/test_result.html.twig', [
            'test' => $test,
            'score' => 67,
            'scoreClass' => 'score-medium',
            'grade' => '3 (удовлетворительно)',
            'gradeColor' => 'warning',
            'gradeMessage' => 'Хорошо, но есть над чем поработать',
            'correctAnswers' => 2,
            'totalQuestions' => 3,
            'timeSpent' => '15:30',
            'questions' => $questions
        ]);
    }

    #[Route('/test/{id}/submit', name: 'app_test_submit', methods: ['POST'])]
    public function submit(Request $request, int $id): Response
    {
        // Заглушка для обработки результатов теста
        // В реальном приложении здесь будет сохранение результатов в БД
        return $this->redirectToRoute('app_test_result', ['id' => $id]);
    }
}