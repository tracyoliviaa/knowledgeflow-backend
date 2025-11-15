<?php
// src/Service/OpenAIService.php

namespace App\Service;

use OpenAI;

class OpenAIService
{
    private $client;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini')
    {
        $this->client = OpenAI::client($apiKey);
        $this->model = $model;
    }

    /**
     * Erstellt eine Zusammenfassung aus Text
     */
    public function summarize(string $text, int $maxWords = 100): string
    {
        // Wenn Text bereits kurz ist, nicht zusammenfassen
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

        return trim($response->choices[0]->message->content);
    }

    /**
     * Generiert Lernkarten aus Text
     */
    public function generateFlashcards(string $text, int $count = 5): array
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

        $json = json_decode($response->choices[0]->message->content, true);
        return $json['flashcards'] ?? [];
    }
}