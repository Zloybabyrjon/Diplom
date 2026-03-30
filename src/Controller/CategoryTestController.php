<?php
// src/Controller/TestController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryTestController extends AbstractController
{
    #[Route('/tests/category', name: 'app_tests_by_category')]
    public function category(): Response
    {
        return $this->render('tests_by_category.html.twig');
    }
    
    #[Route('/tests', name: 'app_test_list')]
    public function list(): Response
    {
        return $this->render('test_list.html.twig');
    }
}