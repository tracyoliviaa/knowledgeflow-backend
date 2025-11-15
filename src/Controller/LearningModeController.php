<?php

namespace App\Controller;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/learning')]
class LearningModeController extends AbstractController
{
    private \OpenAI\Contracts\ClientContract $client;

    public function __construct(
        private EntityManagerInterface $em,
        string $openaiApiKey
    ) {
        $this->client = OpenAI::client($openaiApiKey);
    }

    /**
     * POST /api/learning/ask
     * Body:
     * {
     *   "itemId": 123,
     *   "question": "Was bedeutet X?",
     *   "conversationHistory": [{ role: 'user', content: '...' }]
     * }
     */
    #[Route('/ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $itemId = $data['itemId'] ?? null;
        $question = $data['question'] ?? null;
        $history = $data['conversationHistory'] ?? [];

        if (!$question) {
            return $this->json(['error' => 'Question required'], 400);
        }

        // Item laden
        /** @var Item|null $item */
        $item = $this->em->getRepository(Item::class)->find($itemId);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }

        // Kontext aus Item
        $context = $item->getContent() ?: $item->getTitle();

        // AI-Konversation vorbereiten
        $messages = [];

        // SYSTEM Message
        $messages[] = [
            'role' => 'system',
            'content' =>
                "Du bist ein geduldiger Tutor.\n" .
                "Erkläre Inhalte verständlich, einfach und in kleinen Schritten.\n" .
                "Frage am Ende immer, ob der Nutzer noch weitergehende Infos möchte.\n" .
                "Kontext:\n" . $context
        ];

        // Bisherige Messages hinzufügen
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // Neue Nutzerfrage
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        try {
            // Anfrage an OpenAI
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.6,
                'max_tokens' => 500,
            ]);

            $answer = $response->choices[0]->message->content;

            return $this->json([
                'answer' => $answer,
                'conversationHistory' => array_merge(
                    $history,
                    [
                        ['role' => 'user', 'content' => $question],
                        ['role' => 'assistant', 'content' => $answer]
                    ]
                )
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'AI request failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
