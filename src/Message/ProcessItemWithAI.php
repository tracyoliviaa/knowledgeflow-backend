<?php

namespace App\Message;

class ProcessItemWithAI
{
    private int $itemId;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }
}
