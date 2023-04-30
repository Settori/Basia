<?php

namespace App\Controller;

use App\Entity\ToDoItem;
use App\Repository\AccessTokenRepository;
use App\Repository\ToDoItemRepository;
use App\Security\TokenTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/todo', name: 'todo.')]
class ToDoController extends ApiController
{
    use TokenTrait;

    public function __construct(
        private AccessTokenRepository $accessTokenRepository,
        private ToDoItemRepository $toDoItemRepository
    ) {}

    #[Route('/get', name: 'get')]
    public function get(Request $request): JsonResponse
    {
        $token = $request->headers->get('x-auth-token');
        $user = $this->getUserByToken($token);

        $toDoCollection = $this->toDoItemRepository->findBy([
            'user' => $user
        ]);

        $result = [];

        foreach ($toDoCollection as $toDoItem) {
            $result[] = $toDoItem->toArray();
        }
        // dd($todo);
        return new JsonResponse([
            'data' => (array) $result
        ]);
    }

    #[Route('/add', name: 'post')]
    public function add(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $request->headers->get('x-auth-token');
        $user = $this->getUserByToken($token);
        $data = json_decode($request->getContent(), true);
        
        $content = $this->getContent($data);

        $toDoItem = new ToDoItem();
        $toDoItem->setContent($content);
        $toDoItem->setUser($user);
        $toDoItem->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($toDoItem);
        $entityManager->flush();

        return new JsonResponse([
            'message' => "added"
        ]);
    }

    private function getContent(array $data): string
    {
        if (!array_key_exists('content', $data) || empty($data['content'])) {
            throw new \Exception('Content not provided');
        }

        return (string) $data['content'];
    }
}
