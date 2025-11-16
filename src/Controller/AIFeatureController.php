<?php
// src/Controller/AIFeaturesController.php

namespace App\Controller;

use App\Entity\Item;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
#[IsGranted('ROLE_USER')]
class AIFeaturesController extends AbstractController
{
    public function __construct(
        private OpenAIService $aiService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/summarize/{id}', methods: ['POST'])]
    public function summarize(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            $summary = $this->aiService->summarize($text, $this->getUser(), $item);

            return $this->json(['summary' => $summary]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Summarization failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/flashcards/{id}', methods: ['POST'])]
    public function flashcards(int $id, Request $request): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $count = $data['count'] ?? 5;

            $text = $item->getContent() ?? $item->getTitle();
            $flashcards = $this->aiService->generateFlashcards($text, $this->getUser(), $item, $count);

            return $this->json(['flashcards' => $flashcards]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Flashcard generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/topics/{id}', methods: ['POST'])]
    public function topics(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            $topics = $this->aiService->suggestTopics($text, $this->getUser(), $item);

            return $this->json(['topics' => $topics]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Topic suggestion failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/insights/{id}', methods: ['POST'])]
    public function insights(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            $insights = $this->aiService->extractInsights($text, $this->getUser(), $item);

            return $this->json(['insights' => $insights]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Insight extraction failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}