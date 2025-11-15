<?php
// tests/Service/OpenAIServiceTest.php

namespace App\Tests\Service;

use App\Service\OpenAIService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OpenAIServiceTest extends KernelTestCase
{
    private OpenAIService $service;
    private User $testUser;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->service = static::getContainer()->get(OpenAIService::class);
        
        // Test-User erstellen
        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
    }

    public function testSummarize(): void
    {
        $longText = str_repeat('Dies ist ein langer Text zum Testen. ', 50);
        
        $summary = $this->service->summarize(
            text: $longText,
            user: $this->testUser,
            maxLength: 50
        );

        $this->assertNotEmpty($summary);
        $this->assertLessThan(str_word_count($longText), str_word_count($summary));
    }

    public function testGenerateFlashcards(): void
    {
        $text = 'Photosynthese ist der Prozess, bei dem Pflanzen Lichtenergie nutzen, 
                 um Kohlendioxid und Wasser in Glucose und Sauerstoff umzuwandeln.';
        
        $flashcards = $this->service->generateFlashcards(
            text: $text,
            user: $this->testUser,
            count: 3
        );

        $this->assertCount(3, $flashcards);
        $this->assertArrayHasKey('question', $flashcards[0]);
        $this->assertArrayHasKey('answer', $flashcards[0]);
    }

    public function testSuggestTopics(): void
    {
        $text = 'Künstliche Intelligenz revolutioniert die Softwareentwicklung. 
                 Machine Learning und neuronale Netze ermöglichen neue Anwendungen.';
        
        $topics = $this->service->suggestTopics(
            text: $text,
            user: $this->testUser
        );

        $this->assertNotEmpty($topics);
        $this->assertIsArray($topics);
        $this->assertContainsOnly('string', $topics);
    }
}