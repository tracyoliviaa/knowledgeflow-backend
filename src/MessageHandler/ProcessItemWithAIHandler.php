<?php
// src/MessageHandler/ProcessItemWithAIHandler.php

namespace App\MessageHandler;

use App\Message\ProcessItemWithAI;
use App\Repository\ItemRepository;
use App\Service\OpenAIService;
use App\Service\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessItemWithAIHandler
{
    public function __construct(
        private ItemRepository $itemRepo,
        private OpenAIService $aiService,
        private EmbeddingService $embeddingService,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ProcessItemWithAI $message): void
    {
        $itemId = $message->getItemId();
        
        try {
            $item = $this->itemRepo->find($itemId);

            if (!$item) {
                $this->logger->warning("Item {$itemId} not found for AI processing");
                return;
            }

            $text = $item->getContent() ?? $item->getTitle();
            $user = $item->getUser();

            // 1. Zusammenfassung erstellen (falls Text lang genug)
            if (str_word_count($text) > 150) {
                $summary = $this->aiService->summarize($text, $user, $item, maxLength: 100);
                $item->setSummary($summary);
                $this->logger->info("Created summary for item {$itemId}");
            }

            // 2. Embedding erstellen für semantische Suche
            $embedding = $this->embeddingService->createEmbedding($text);
            $item->setEmbedding($embedding);
            $this->logger->info("Created embedding for item {$itemId}");

            // 3. Themen vorschlagen (optional)
            if ($item->getTopics()->isEmpty()) {
                $suggestedTopics = $this->aiService->suggestTopics($text, $user, $item);
                // Topics würden hier zugewiesen werden
                $this->logger->info("Suggested topics for item {$itemId}: " . implode(', ', $suggestedTopics));
            }

            $this->em->flush();

        } catch (\Exception $e) {
            $this->logger->error("Failed to process item {$itemId}: " . $e->getMessage());
            throw $e; // Retry durch Messenger
        }
    }
}