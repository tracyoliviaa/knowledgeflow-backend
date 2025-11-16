<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
#[IsGranted('ROLE_USER')]
class AIUsageController extends AbstractController
{
    #[Route('/usage-stats', name: 'ai_usage_stats', methods: ['GET'])]
    public function usageStats(): JsonResponse
    {
        // Placeholder data - replace with real database queries later
        return $this->json([
            'current_month' => [
                'total_cost' => 0.0023,
                'operations' => [
                    [
                        'operation' => 'summarize',
                        'count' => '5',
                        'cost' => '0.0015'
                    ],
                    [
                        'operation' => 'flashcards',
                        'count' => '2',
                        'cost' => '0.0008'
                    ]
                ]
            ],
            'all_time' => [
                'total_requests' => 15,
                'total_input_tokens' => 2500,
                'total_output_tokens' => 1200,
                'total_tokens' => 3700,
                'total_cost' => 0.0123
            ]
        ]);
    }
}