<?php
// src/EventSubscriber/ItemCreatedSubscriber.php

namespace App\EventSubscriber;

use App\Entity\Item;
use App\Service\OpenAIService;
use App\Service\EmbeddingService;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ProcessItemWithAI;

class ItemCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

   public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist, // Wird nach dem Speichern ausgelöst
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Nur bei neuen Items reagieren
        if (!$entity instanceof Item) {
            return;
        }

        // Asynchrone Verarbeitung per Message Queue
        // (verhindert, dass User warten muss)
        $this->bus->dispatch(new ProcessItemWithAI($entity->getId()));
    }
}