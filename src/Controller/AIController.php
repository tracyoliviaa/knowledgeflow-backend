<?php
// src/Controller/AIUsageController.php

namespace App\Controller;

use App\Repository\AIUsageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
#[IsGranted('ROLE_USER')]
class AIUsageController extends AbstractController
{
    public function __construct(
        private AIUsageRepository $usageRepo
    ) {}

    #[Route('/usage-stats', methods: ['GET'])]
    public function getUsageStats(): JsonResponse
    {
        $user = $this->getUser();

        // Current month statistics
        $currentMonthCost = $this->usageRepo->getCurrentMonthCost($user);
        $currentMonthStats = $this->usageRepo->getCurrentMonthStats($user);

        // All-time statistics
        $allTimeStats = $this->usageRepo->getTotalStats($user);

        return $this->json([
            'current_month' => [
                'total_cost' => $currentMonthCost,
                'operations' => $currentMonthStats,
            ],
            'all_time' => [
                'total_requests' => (int) $allTimeStats['total_requests'],
                'total_input_tokens' => (int) $allTimeStats['total_input_tokens'],
                'total_output_tokens' => (int) $allTimeStats['total_output_tokens'],
                'total_tokens' => (int) $allTimeStats['total_input_tokens'] + (int) $allTimeStats['total_output_tokens'],
                'total_cost' => (float) $allTimeStats['total_cost'],
            ]
        ]);
    }

    #[Route('/usage-limit', methods: ['GET'])]
    public function checkUsageLimit(): JsonResponse
    {
        $user = $this->getUser();
        $currentMonthCost = $this->usageRepo->getCurrentMonthCost($user);

        // Example: $10/month limit
        $limit = 10.00;
        $remaining = max(0, $limit - $currentMonthCost);
        $percentage = min(100, ($currentMonthCost / $limit) * 100);

        return $this->json([
            'limit' => $limit,
            'used' => $currentMonthCost,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'exceeded' => $currentMonthCost >= $limit,
        ]);
    }
}