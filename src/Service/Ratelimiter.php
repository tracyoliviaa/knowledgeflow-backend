<?php
// src/Service/RateLimiter.php

namespace App\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class AIRateLimiter
{
    private $cache;

    public function __construct()
    {
        // Redis-Cache (oder FilesystemAdapter als Alternative)
        $this->cache = new RedisAdapter(
            RedisAdapter::createConnection('redis://localhost')
        );
    }

    /**
     * Prüft, ob User noch Requests machen darf
     * Max. 10 AI-Requests pro Stunde
     */
    public function canMakeRequest(int $userId): bool
    {
        $key = "ai_rate_limit_user_{$userId}";
        $item = $this->cache->getItem($key);

        $requests = $item->isHit() ? $item->get() : 0;

        if ($requests >= 10) {
            return false;
        }

        $item->set($requests + 1);
        $item->expiresAfter(3600); // 1 Stunde
        $this->cache->save($item);

        return true;
    }

    public function getRemainingRequests(int $userId): int
    {
        $key = "ai_rate_limit_user_{$userId}";
        $item = $this->cache->getItem($key);
        $used = $item->isHit() ? $item->get() : 0;
        
        return max(0, 10 - $used);
    }
}