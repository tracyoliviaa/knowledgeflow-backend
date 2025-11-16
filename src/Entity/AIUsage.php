<?php
// src/Entity/AIUsage.php

namespace App\Entity;

use App\Repository\AIUsageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AIUsageRepository::class)]
#[ORM\Table(name: 'ai_usage')]
#[ORM\Index(name: 'idx_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_operation', columns: ['operation'])]
class AIUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Item $item = null;

    #[ORM\Column(length: 50)]
    private ?string $operation = null;

    #[ORM\Column(length: 50)]
    private ?string $model = null;

    #[ORM\Column]
    private ?int $inputTokens = null;

    #[ORM\Column]
    private ?int $outputTokens = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    private ?string $cost = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;
        return $this;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): static
    {
        $this->operation = $operation;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getInputTokens(): ?int
    {
        return $this->inputTokens;
    }

    public function setInputTokens(int $inputTokens): static
    {
        $this->inputTokens = $inputTokens;
        return $this;
    }

    public function getOutputTokens(): ?int
    {
        return $this->outputTokens;
    }

    public function setOutputTokens(int $outputTokens): static
    {
        $this->outputTokens = $outputTokens;
        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Calculate cost based on tokens and model pricing
     */
    public function calculateCost(): void
    {
        // GPT-4o-mini pricing (as of 2024)
        // Input: $0.15 per 1M tokens
        // Output: $0.60 per 1M tokens
        
        $inputCost = ($this->inputTokens / 1_000_000) * 0.15;
        $outputCost = ($this->outputTokens / 1_000_000) * 0.60;
        
        $this->cost = (string) ($inputCost + $outputCost);
    }
}