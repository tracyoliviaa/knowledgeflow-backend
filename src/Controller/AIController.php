<?php

namespace App\Controller;

use App\Service\OpenAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AIController extends AbstractController
{
    private OpenAIService $aiService;

    public function __construct(OpenAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    #[Route('/api/ai/test', name: 'ai_test', methods: ['POST', 'GET'])]
    public function test(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'GET') {
            return $this->json([
                'message' => 'AI Controller works! Use POST with {"text":"..."}'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? 'Hallo Welt';

        try {
            $summary = $this->aiService->summarize($text);
            return $this->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}