<?php
// src/Controller/AIController.php

namespace App\Controller;

use App\Service\OpenAIService;
use App\Repository\ItemRepository;
use App\Repository\TopicRepository;
use App\Service\RateLimiterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
#[IsGranted('ROLE_USER')]
class AIController extends AbstractController
{
    public function __construct(
        private OpenAIService $aiService,
        private EntityManagerInterface $em,
        private RateLimiterService $rateLimiter
    ) {}

    /**
     * POST /api/ai/summarize
     * Body: {"itemId": 123}
     */
    #[Route('/summarize', methods: ['POST'])]
    public function summarize(
        Request $request,
        ItemRepository $itemRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $itemId = $data['itemId'] ?? null;

        if (!$itemId) {
            return $this->json(['error' => 'itemId required'], 400);
        }

        $item = $itemRepo->find($itemId);

        // Security: Nur eigene Items
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        // 🚧 RATE LIMIT CHECK
        if (!$this->rateLimiter->canMakeRequest($this->getUser()->getId())) {
            return $this->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Du hast dein Limit von 10 AI-Requests pro Stunde erreicht.'
            ], 429);
        }

        $text = $item->getContent() ?? $item->getTitle();

        if (empty($text)) {
            return $this->json(['error' => 'No content to summarize'], 400);
        }

        try {
            $summary = $this->aiService->summarize($text, maxLength: 150);

            $item->setSummary($summary);
            $this->em->flush();

            return $this->json([
                'summary' => $summary,
                'cost' => $this->aiService->estimateCost($text)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'AI request failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/ai/generate-flashcards
     * Body: {"itemId": 123, "count": 5}
     */
    #[Route('/generate-flashcards', methods: ['POST'])]
    public function generateFlashcards(
        Request $request,
        ItemRepository $itemRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $itemId = $data['itemId'] ?? null;
        $count = $data['count'] ?? 5;

        $item = $itemRepo->find($itemId);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        // 🚧 RATE LIMIT CHECK
        if (!$this->rateLimiter->canMakeRequest($this->getUser()->getId())) {
            return $this->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Du hast dein Limit von 10 AI-Requests pro Stunde erreicht.'
            ], 429);
        }

        $text = $item->getContent() ?? $item->getTitle();

        try {
            $flashcards = $this->aiService->generateFlashcards($text, $count);

            foreach ($flashcards as $cardData) {
                $flashcard = new \App\Entity\Flashcard();
                $flashcard->setQuestion($cardData['question']);
                $flashcard->setAnswer($cardData['answer']);
                $flashcard->setItem($item);
                $flashcard->setNextReviewDate(new \DateTime());

                $this->em->persist($flashcard);
            }
            $this->em->flush();

            return $this->json([
                'flashcards' => $flashcards,
                'message' => count($flashcards) . ' Lernkarten erstellt'
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate flashcards',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/ai/suggest-topics
     * Body: {"itemId": 123}
     */
    #[Route('/suggest-topics', methods: ['POST'])]
    public function suggestTopics(
        Request $request,
        ItemRepository $itemRepo,
        TopicRepository $topicRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $itemId = $data['itemId'] ?? null;

        $item = $itemRepo->find($itemId);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        // 🚧 RATE LIMIT CHECK
        if (!$this->rateLimiter->canMakeRequest($this->getUser()->getId())) {
            return $this->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Du hast dein Limit von 10 AI-Requests pro Stunde erreicht.'
            ], 429);
        }

        $existingTopics = array_map(
            fn($topic) => $topic->getName(),
            $topicRepo->findBy(['user' => $this->getUser()])
        );

        $text = $item->getContent() ?? $item->getTitle();

        try {
            $suggestedTopics = $this->aiService->suggestTopics($text, $existingTopics);

            return $this->json([
                'suggestions' => $suggestedTopics,
                'existing' => $existingTopics
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to suggest topics'], 500);
        }
    }

    /**
     * POST /api/ai/extract-insights
     * Body: {"itemId": 123}
     */
    #[Route('/extract-insights', methods: ['POST'])]
    public function extractInsights(
        Request $request,
        ItemRepository $itemRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $itemId = $data['itemId'] ?? null;

        $item = $itemRepo->find($itemId);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        // 🚧 RATE LIMIT CHECK
        if (!$this->rateLimiter->canMakeRequest($this->getUser()->getId())) {
            return $this->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Du hast dein Limit von 10 AI-Requests pro Stunde erreicht.'
            ], 429);
        }

        $text = $item->getContent() ?? $item->getTitle();

        try {
            $takeaways = $this->aiService->extractKeyTakeaways($text);

            return $this->json(['takeaways' => $takeaways]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to extract insights'], 500);
        }
    }

    /**
     * GET /api/ai/usage-stats
     */
    #[Route('/usage-stats', methods: ['GET'])]
    public function usageStats(): JsonResponse
    {
        return $this->json([
            'current_month' => [
                'requests' => 42,
                'estimated_cost' => 0.15,
                'tokens_used' => 25000
            ],
            'pricing' => [
                'model' => 'gpt-4o-mini',
                'input' => '$0.15 per 1M tokens',
                'output' => '$0.60 per 1M tokens'
            ]
        ]);
    }
}
