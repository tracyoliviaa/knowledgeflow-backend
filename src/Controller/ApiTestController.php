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

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Temporärer Test-Login (ohne echte Authentifizierung)
        return $this->json([
            'token' => 'test-token-123',
            'user' => [
                'id' => 1,
                'email' => 'test@example.com'
            ]
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(): JsonResponse
    {
        return $this->json([
            'token' => 'test-token-456',
            'user' => [
                'id' => 2,
                'email' => 'new@example.com'
            ]
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
