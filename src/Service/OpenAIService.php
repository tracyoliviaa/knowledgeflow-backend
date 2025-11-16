<?php
// src/Service/OpenAIService.php

namespace App\Service;

use App\Entity\AIUsage;
use App\Entity\Item;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;

class OpenAIService
{
    private $client;
    private string $model;

    public function __construct(
        string $apiKey,
        private EntityManagerInterface $em,
        string $model = 'gpt-4o-mini'
    ) {
        $this->client = OpenAI::client($apiKey);
        $this->model = $model;
    }

    /**
     * Track AI usage for billing/analytics
     */
    private function trackUsage(
        User $user,
        string $operation,
        int $inputTokens,
        int $outputTokens,
        ?Item $item = null
    ): void {
        $usage = new AIUsage();
        $usage->setUser($user);
        $usage->setItem($item);
        $usage->setOperation($operation);
        $usage->setModel($this->model);
        $usage->setInputTokens($inputTokens);
        $usage->setOutputTokens($outputTokens);
        $usage->calculateCost();

        $this->em->persist($usage);
        $this->em->flush();
    }

    /**
     * Erstellt eine Zusammenfassung aus Text
     */
    public function summarize(string $text, User $user, ?Item $item = null, int $maxWords = 100): string
    {
        if (str_word_count($text) <= $maxWords) {
            return $text;
        }

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für prägnante Zusammenfassungen. 
                                  Erstelle eine klare Zusammenfassung in deutscher Sprache.'
                ],
                [
                    'role' => 'user',
                    'content' => "Fasse folgenden Text in maximal {$maxWords} Wörtern zusammen:\n\n{$text}"
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);

        // Track usage
        $this->trackUsage(
            $user,
            'summarize',
            $response->usage->promptTokens,
            $response->usage->completionTokens,
            $item
        );

        return trim($response->choices[0]->message->content);
    }

    /**
     * Generiert Lernkarten aus Text
     */
    public function generateFlashcards(string $text, User $user, ?Item $item = null, int $count = 5): array
    {
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Lerncoach. Erstelle präzise Lernkarten.
                                  Antworte NUR mit JSON: {"flashcards": [{"question": "...", "answer": "..."}]}'
                ],
                [
                    'role' => 'user',
                    'content' => "Erstelle {$count} Lernkarten aus folgendem Text:\n\n{$text}"
                ]
            ],
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object'],
        ]);

        // Track usage
        $this->trackUsage(
            $user,
            'generate_flashcards',
            $response->usage->promptTokens,
            $response->usage->completionTokens,
            $item
        );

        $json = json_decode($response->choices[0]->message->content, true);
        return $json['flashcards'] ?? [];
    }

    /**
     * Schlägt Themen vor
     */
    public function suggestTopics(string $text, User $user, ?Item $item = null): array
    {
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Themenextraktion. 
                                  Antworte NUR mit JSON: {"topics": ["Thema1", "Thema2", ...]}'
                ],
                [
                    'role' => 'user',
                    'content' => "Extrahiere 3-5 Hauptthemen aus folgendem Text:\n\n{$text}"
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
        ]);

        // Track usage
        $this->trackUsage(
            $user,
            'suggest_topics',
            $response->usage->promptTokens,
            $response->usage->completionTokens,
            $item
        );

        $json = json_decode($response->choices[0]->message->content, true);
        return $json['topics'] ?? [];
    }

    /**
     * Extrahiert wichtige Erkenntnisse
     */
    public function extractInsights(string $text, User $user, ?Item $item = null): array
    {
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Wissensextraktion.
                                  Antworte NUR mit JSON: {"insights": ["Erkenntnis1", "Erkenntnis2", ...]}'
                ],
                [
                    'role' => 'user',
                    'content' => "Extrahiere die 3-5 wichtigsten Erkenntnisse aus folgendem Text:\n\n{$text}"
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
        ]);

        // Track usage
        $this->trackUsage(
            $user,
            'extract_insights',
            $response->usage->promptTokens,
            $response->usage->completionTokens,
            $item
        );

        $json = json_decode($response->choices[0]->message->content, true);
        return $json['insights'] ?? [];
    }
}