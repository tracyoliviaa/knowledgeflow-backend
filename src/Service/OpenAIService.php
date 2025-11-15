<?php
// src/Service/OpenAIService.php (Erweiterung)

namespace App\Service;

use App\Entity\AIUsage;
use App\Entity\User;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;

class OpenAIService
{
    private $client;
    private string $model;
    private EntityManagerInterface $em;

    public function __construct(
        string $apiKey, 
        string $model,
        EntityManagerInterface $em
    ) {
        $this->client = OpenAI::client($apiKey);
        $this->model = $model;
        $this->em = $em;
    }

    /**
     * Wrapper für API-Calls mit automatischem Usage-Tracking
     */
    private function makeRequest(
        array $messages,
        float $temperature,
        int $maxTokens,
        User $user,
        string $operation,
        ?Item $item = null,
        array $options = []
    ): object {
        $params = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        // Optional: JSON-Format erzwingen
        if (isset($options['json']) && $options['json']) {
            $params['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->client->chat()->create($params);

        // Usage-Daten tracken
        $usage = $response->usage;
        $this->trackUsage(
            $user,
            $operation,
            $usage->promptTokens,
            $usage->completionTokens,
            $item
        );

        return $response;
    }

    /**
     * Speichert Usage-Daten in der Datenbank
     */
    private function trackUsage(
        User $user,
        string $operation,
        int $inputTokens,
        int $outputTokens,
        ?Item $item = null
    ): void {
        // Kosten berechnen (gpt-4o-mini Preise)
        $inputCost = ($inputTokens / 1_000_000) * 0.15;
        $outputCost = ($outputTokens / 1_000_000) * 0.60;
        $totalCost = $inputCost + $outputCost;

        $aiUsage = new AIUsage();
        $aiUsage->setUser($user);
        $aiUsage->setOperation($operation);
        $aiUsage->setInputTokens($inputTokens);
        $aiUsage->setOutputTokens($outputTokens);
        $aiUsage->setCost($totalCost);
        $aiUsage->setModel($this->model);
        $aiUsage->setItem($item);

        $this->em->persist($aiUsage);
        $this->em->flush();
    }

    /**
     * Zusammenfassung erstellen (mit Tracking)
     */
    public function summarize(
        string $text, 
        User $user, 
        ?Item $item = null,
        int $maxLength = 100
    ): string {
        if (str_word_count($text) <= $maxLength) {
            return $text;
        }

        $response = $this->makeRequest(
            messages: [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für prägnante Zusammenfassungen. 
                                  Erstelle eine klare, strukturierte Zusammenfassung in deutscher Sprache.
                                  Fokussiere auf die wichtigsten Kernaussagen.'
                ],
                [
                    'role' => 'user',
                    'content' => "Fasse folgenden Text in maximal {$maxLength} Wörtern zusammen:\n\n{$text}"
                ]
            ],
            temperature: 0.3,
            maxTokens: 500,
            user: $user,
            operation: 'summarize',
            item: $item
        );

        return trim($response->choices[0]->message->content);
    }

    /**
     * Lernkarten generieren (mit Tracking)
     */
    public function generateFlashcards(
        string $text,
        User $user,
        ?Item $item = null,
        int $count = 5
    ): array {
        $response = $this->makeRequest(
            messages: [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Lerncoach. Erstelle präzise Lernkarten (Flashcards) 
                                  mit klaren Fragen und kurzen, prägnanten Antworten.
                                  Antworte NUR mit JSON im Format:
                                  {"flashcards": [{"question": "...", "answer": "..."}, ...]}'
                ],
                [
                    'role' => 'user',
                    'content' => "Erstelle {$count} Lernkarten aus folgendem Text:\n\n{$text}"
                ]
            ],
            temperature: 0.5,
            maxTokens: 1000,
            user: $user,
            operation: 'flashcards',
            item: $item,
            options: ['json' => true]
        );

        $content = $response->choices[0]->message->content;
        $json = json_decode($content, true);

        return $json['flashcards'] ?? [];
    }

    /**
     * Themen vorschlagen (mit Tracking)
     */
    public function suggestTopics(
        string $text,
        User $user,
        ?Item $item = null,
        array $existingTopics = []
    ): array {
        $existingTopicsText = empty($existingTopics) 
            ? '' 
            : "\n\nBevorzuge wenn möglich diese bestehenden Kategorien: " . implode(', ', $existingTopics);

        $response = $this->makeRequest(
            messages: [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Kategorisierung. 
                                  Analysiere den Text und schlage 3-5 passende Themenbereiche vor.
                                  Antworte NUR mit einem JSON im Format: {"topics": ["Thema1", "Thema2", ...]}
                                  Themen sollten präzise, aber nicht zu spezifisch sein.'
                                  . $existingTopicsText
                ],
                [
                    'role' => 'user',
                    'content' => "Welche Themen passen zu diesem Text?\n\n{$text}"
                ]
            ],
            temperature: 0.4,
            maxTokens: 300,
            user: $user,
            operation: 'suggest_topics',
            item: $item,
            options: ['json' => true]
        );

        $content = $response->choices[0]->message->content;
        $json = json_decode($content, true);

        return $json['topics'] ?? [];
    }
}