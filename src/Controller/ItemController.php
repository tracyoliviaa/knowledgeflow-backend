<?php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/items')]
#[IsGranted('ROLE_USER')]
class ItemController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ItemRepository $repository): JsonResponse
    {
        $items = $repository->findBy(['user' => $this->getUser()]);
        return $this->json(['data' => array_map([$this, 'serialize'], $items)]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, ItemRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }
        
        return $this->json(['data' => $this->serialize($item)]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title'])) {
            return $this->json(['error' => 'Title is required'], 400);
        }
        
        $item = new Item();
        $item->setTitle($data['title']);
        $item->setContent($data['content'] ?? '');
        $item->setType($data['type'] ?? 'note');
        $item->setUser($this->getUser());

        $em->persist($item);
        $em->flush();

        return $this->json(['data' => $this->serialize($item)], 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, ItemRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['title'])) {
            $item->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $item->setContent($data['content']);
        }
        if (isset($data['type'])) {
            $item->setType($data['type']);
        }
        
        $em->flush();
        
        return $this->json(['data' => $this->serialize($item)]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, ItemRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        
        if (!$item || $item->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Item not found'], 404);
        }
        
        $em->remove($item);
        $em->flush();
        
        return $this->json(['message' => 'Item deleted successfully']);
    }

    private function serialize(Item $item): array
    {
        return [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'content' => $item->getContent(),
            'type' => $item->getType(),
            'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}