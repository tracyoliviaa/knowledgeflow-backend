<?php
// src/Controller/ItemController.php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/items')]
#[IsGranted('ROLE_USER')]
class ItemController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ItemRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        $items = $repository->findByUser($user);

        return $this->json([
            'data' => array_map(fn($item) => $this->serializeItem($item), $items)
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $item = new Item();
        $item->setTitle($data['title'] ?? 'Untitled');
        $item->setContent($data['content'] ?? null);
        $item->setUrl($data['url'] ?? null);
        $item->setType($data['type'] ?? 'note');
        $item->setSource($data['source'] ?? 'manual');
        $item->setUser($this->getUser());

        $em->persist($item);
        $em->flush();

        return $this->json([
            'message' => 'Item created',
            'data' => $this->serializeItem($item)
        ], 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, ItemRepository $repository): JsonResponse
    {
        $item = $repository->find($id);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return $this->json(['data' => $this->serializeItem($item)]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ItemRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $item = $repository->find($id);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $item->setTitle($data['title']);
        if (isset($data['content'])) $item->setContent($data['content']);
        if (isset($data['url'])) $item->setUrl($data['url']);
        if (isset($data['type'])) $item->setType($data['type']);

        $em->flush();

        return $this->json([
            'message' => 'Item updated',
            'data' => $this->serializeItem($item)
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        ItemRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $item = $repository->find($id);

        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $em->remove($item);
        $em->flush();

        return $this->json(['message' => 'Item deleted'], 204);
    }

    private function serializeItem(Item $item): array
    {
        return [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'content' => $item->getContent(),
            'url' => $item->getUrl(),
            'type' => $item->getType(),
            'source' => $item->getSource(),
            'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}