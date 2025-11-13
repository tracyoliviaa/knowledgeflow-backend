<?php
// src/Controller/ApiTestController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiTestController extends AbstractController
{
    #[Route('/test', name: 'test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'message' => 'Backend is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

   
    #[Route('/user', name: 'user', methods: ['GET'])]
    public function user(): JsonResponse
    {
        return $this->json([
            'id' => 1,
            'email' => 'test@example.com'
        ]);
    }
}
