<?php
// src/Controller/LearningModeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenAI;

#[Route('/api/learning')]
class LearningModeController extends AbstractController
{
    private $client;

    public function __construct(string $openaiApiKey)
    {
        $this->client = OpenAI::client($openaiApiKey);
    }

    /**
     * POST /api/learning/ask
     * Body: {"itemId": 123, "question": "Was bedeutet X?", "conversationHistory": [...]}
     */
    #[Route('/ask', methods: ['POST'])]
    public function askQuestion(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';
        $itemId = $data['itemId'] ?? null;
        $history = $data['conversationHistory'] ?? [];

        if (empty($question)) {
            return $this->json(['error' => 'Question required'], 400);
        }

        // Item-Content als Kontext laden
        $item = $this->getDoctrine()->getRepository(\App\Entity\Item::class)->find($itemId);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        $context = $item->getContent() ?? $item->getTitle();

        // Conversation History aufbauen
        $messages = [
            [
                'role' => 'system',
                'content' => 'Du bist ein geduldiger Tutor. Beantworte Fragen zum folgenden Text.
                              Erkläre verständlich und frage nach, ob mehr Details gewünscht sind.
                              Kontext: ' . $context
            ]
        ];

        // Bisherige Konversation hinzufügen
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // Aktuelle Frage hinzufügen
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.7, // Etwas kreativer für Tutoring
                'max_tokens' => 500,
            ]);

            $answer = $response->choices[0]->message->content;

            return $this->json([
                'answer' => $answer,
                'conversationHistory' => array_merge($history, [
                    ['role' => 'user', 'content' => $question],
                    ['role' => 'assistant', 'content' => $answer]
                ])
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to get answer'], 500);
        }
    }
}