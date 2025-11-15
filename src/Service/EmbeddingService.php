<?php
// src/Service/EmbeddingService.php

namespace App\Service;

use OpenAI;

class EmbeddingService
{
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Erstellt ein Embedding (Vektor) aus Text
     * Embeddings ermöglichen semantische Ähnlichkeitssuche
     */
    public function createEmbedding(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'model' => 'text-embedding-3-small', // Günstiges, gutes Modell
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding; // Array von 1536 Floats
    }

    /**
     * Berechnet Kosinus-Ähnlichkeit zwischen zwei Vektoren
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] ** 2;
            $magnitude2 += $vec2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Findet ähnliche Items basierend auf Semantik (nicht nur Keywords)
     */
    public function findSimilar(string $query, array $items, int $limit = 5): array
    {
        $queryEmbedding = $this->createEmbedding($query);

        $similarities = [];

        foreach ($items as $item) {
            // Annahme: Item hat bereits ein gespeichertes Embedding
            $itemEmbedding = json_decode($item->getEmbedding(), true);
            
            $similarity = $this->cosineSimilarity($queryEmbedding, $itemEmbedding);
            
            $similarities[] = [
                'item' => $item,
                'similarity' => $similarity
            ];
        }

        // Nach Ähnlichkeit sortieren
        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($similarities, 0, $limit);
    }
}