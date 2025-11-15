<?php
// src/Service/CachedOpenAIService.php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedOpenAIService
{
    public function __construct(
        private OpenAIService $openAIService,
        private CacheInterface $cache
    ) {}

    /**
     * Cached Summarization (identische Texte werden nicht neu verarbeitet)
     */
    public function summarize(string $text, User $user, ?Item $item = null): string
    {
        $cacheKey = 'summary_' . md5($text);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($text, $user) {
            $item->expiresAfter(86400 * 7); // 7 Tage Cache
            
            return $this->openAIService->summarize($text, $user);
        });
    }

    /**
     * Cached Topic Suggestions
     */
    public function suggestTopics(string $text, User $user, ?Item $item = null): array
    {
        $cacheKey = 'topics_' . md5($text);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($text, $user) {
            $item->expiresAfter(86400 * 30); // 30 Tage Cache
            
            return $this->openAIService->suggestTopics($text, $user);
        });
    }
}