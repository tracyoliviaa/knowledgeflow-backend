<?php

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
class AIFeatureController extends AbstractController
{
    public function __construct(
        private OpenAIService $aiService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/summarize/{id}', name: 'ai_summarize', methods: ['POST'])]
    public function summarize(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            // OpenAIService::summarize expects (string $text, User $user)
            $summary = $this->aiService->summarize($text, $this->getUser());
            
            return $this->json(['summary' => $summary]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/flashcards/{id}', name: 'ai_flashcards', methods: ['POST'])]
    public function flashcards(int $id, Request $request): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $count = $data['count'] ?? 5;
            
            $text = $item->getContent() ?? $item->getTitle();
            $flashcards = $this->aiService->generateFlashcards($text, $count);
            
            return $this->json(['flashcards' => $flashcards]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/topics/{id}', name: 'ai_topics', methods: ['POST'])]
    public function topics(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            // Note: suggestTopics method needs to be added to OpenAIService
            $topics = ['AI', 'Technology', 'Learning']; // Placeholder
            
            return $this->json(['topics' => $topics]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/insights/{id}', name: 'ai_insights', methods: ['POST'])]
    public function insights(int $id): JsonResponse
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        try {
            $text = $item->getContent() ?? $item->getTitle();
            // Placeholder insights
            $insights = [
                'Key concept identified',
                'Important takeaway from content',
                'Related topic suggestion'
            ];
            
            return $this->json(['insights' => $insights]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}