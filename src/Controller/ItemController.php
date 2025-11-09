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

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $item = new Item();
        $item->setTitle($data['title'] ?? 'Untitled');
        $item->setContent($data['content'] ?? null);
        $item->setType($data['type'] ?? 'note');
        $item->setUser($this->getUser());

        $em->persist($item);
        $em->flush();

        return $this->json(['data' => $this->serialize($item)], 201);
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