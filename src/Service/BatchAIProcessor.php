<?php
// src/Service/BatchAIProcessor.php

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;

class BatchAIProcessor
{
    public function __construct(
        private OpenAIService $aiService
    ) {}

    /**
     * Verarbeitet mehrere Items gleichzeitig
     * Nützlich für "Alle unzusammengefassten Items verarbeiten"
     */
    public function processBatch(array $items, User $user, string $operation = 'summarize'): array
    {
        $results = [];
        $errors = [];

        foreach ($items as $item) {
            try {
                $result = match($operation) {
                    'summarize' => $this->aiService->summarize(
                        $item->getContent() ?? $item->getTitle(),
                        $user,
                        $item
                    ),
                    'flashcards' => $this->aiService->generateFlashcards(
                        $item->getContent() ?? $item->getTitle(),
                        $user,
                        $item
                    ),
                    'topics' => $this->aiService->suggestTopics(
                        $item->getContent() ?? $item->getTitle(),
                        $user,
                        $item
                    ),
                    default => throw new \Exception('Unknown operation')
                };

                $results[$item->getId()] = $result;

            } catch (\Exception $e) {
                $errors[$item->getId()] = $e->getMessage();
            }

            // Rate Limiting respektieren (nicht mehr als 10/Minute)
            usleep(100000); // 100ms Pause zwischen Requests
        }

        return [
            'success' => $results,
            'errors' => $errors,
            'total' => count($items),
            'processed' => count($results)
        ];
    }
}