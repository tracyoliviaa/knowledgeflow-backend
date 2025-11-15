<?php
// src/Service/KnowledgeGapAnalyzer.php

namespace App\Service;

use App\Entity\User;
use App\Repository\ItemRepository;
use App\Repository\TopicRepository;
use OpenAI;

class KnowledgeGapAnalyzer
{
    public function __construct(
        private ItemRepository $itemRepo,
        private TopicRepository $topicRepo,
        private string $apiKey
    ) {}

    /**
     * Analysiert gespeicherte Inhalte und findet Wissenslücken
     */
    public function analyzeGaps(User $user): array
    {
        $items = $this->itemRepo->findBy(['user' => $user]);
        $topics = $this->topicRepo->findBy(['user' => $user]);

        // Erstelle Wissenslandkarte
        $knowledgeMap = [
            'topics' => array_map(fn($t) => $t->getName(), $topics),
            'item_count' => count($items),
            'content_types' => $this->groupByType($items),
        ];

        $client = OpenAI::client($this->apiKey);

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Lerncoach. Analysiere die Wissenssammlung 
                                  und identifiziere Wissenslücken oder unterrepräsentierte Bereiche.
                                  Gib konkrete Empfehlungen, was gelernt werden sollte.
                                  Antworte mit JSON: {"gaps": [{"area": "...", "recommendation": "..."}]}'
                ],
                [
                    'role' => 'user',
                    'content' => 'Wissenslandkarte: ' . json_encode($knowledgeMap)
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.6,
        ]);

        $result = json_decode($response->choices[0]->message->content, true);

        return $result['gaps'] ?? [];
    }

    private function groupByType(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $type = $item->getType();
            $grouped[$type] = ($grouped[$type] ?? 0) + 1;
        }
        return $grouped;
    }
}