<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Repository\AccessTokenRepository;
use App\Security\TokenTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user', name: 'user.')]
class UserController extends ApiController
{
    use TokenTrait;

    #[Route('/test', name: 'test')]
    public function index(Request $request): JsonResponse
    {
        // It was already validated by ApiKeyAuthenticator
        // But it wouldn't be wrong to add a second validation later
        $token = $request->headers->get('x-auth-token');

        // $accessTokenEntity = $this->accessTokenRepository->findOneByToken($token);
        $user = $this->getUserByToken($token);
        
        return $this->json([
            'user' => $user->getEmail()
        ]);
    }
}
