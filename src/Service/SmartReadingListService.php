<?php
// src/Service/SmartReadingListService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\ItemRepository;
use OpenAI;

class SmartReadingListService
{
    public function __construct(
        private ItemRepository $itemRepo,
        private string $apiKey
    ) {}

    /**
     * KI analysiert User-Items und schlägt Lesereihenfolge vor
     */
    public function generateReadingList(User $user): array
    {
        $items = $this->itemRepo->findBy(['user' => $user], ['createdAt' => 'DESC'], 50);

        // Items als Context für KI aufbereiten
        $itemsSummary = array_map(function($item) {
            return [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'type' => $item->getType(),
                'summary' => $item->getSummary() ?? substr($item->getContent(), 0, 200)
            ];
        }, $items);

        $client = OpenAI::client($this->apiKey);

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Lerncoach. Analysiere die gespeicherten Inhalte 
                                  und schlage eine sinnvolle Lesereihenfolge vor.
                                  Berücksichtige: Themencluster, Schwierigkeitsgrad, Aktualität.
                                  Antworte mit JSON: {"reading_list": [{"item_id": 1, "reason": "..."}]}'
                ],
                [
                    'role' => 'user',
                    'content' => 'Hier sind die gespeicherten Inhalte: ' . json_encode($itemsSummary)
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.5,
        ]);

        $result = json_decode($response->choices[0]->message->content, true);

        return $result['reading_list'] ?? [];
    }
}