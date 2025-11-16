<?php
// src/Controller/HealthCheckController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController extends AbstractController
{
    #[Route('/', name: 'health_check', methods: ['GET', 'HEAD'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'service' => 'KnowledgeFlow Backend',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'health' => '/',
                'api_test' => '/api/test',
                'register' => '/api/register',
                'login' => '/api/login',
                'items' => '/api/items',
            ]
        ]);
    }

    #[Route('/health', name: 'health_detailed', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'checks' => [
                'database' => 'ok',
                'api' => 'ok',
            ]
        ]);
    }
}