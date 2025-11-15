<?php
// src/Entity/AIUsage.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AIUsageRepository::class)]
#[ORM\Table(name: 'ai_usage')]
#[ORM\Index(columns: ['user_id', 'created_at'])]
class AIUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 50)]
    private string $operation; // 'summarize', 'flashcards', 'topics', etc.

    #[ORM\Column(type: 'integer')]
    private int $inputTokens;

    #[ORM\Column(type: 'integer')]
    private int $outputTokens;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    private float $cost; // In USD

    #[ORM\Column(type: 'string', length: 50)]
    private string $model;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Item $item = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getter & Setter...
    
    public function getId(): ?int { return $this->id; }
    
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }
    
    public function getUser(): User { return $this->user; }
    
    public function setOperation(string $operation): self
    {
        $this->operation = $operation;
        return $this;
    }
    
    public function setInputTokens(int $tokens): self
    {
        $this->inputTokens = $tokens;
        return $this;
    }
    
    public function setOutputTokens(int $tokens): self
    {
        $this->outputTokens = $tokens;
        return $this;
    }
    
    public function setCost(float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }
    
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    public function setItem(?Item $item): self
    {
        $this->item = $item;
        return $this;
    }
}