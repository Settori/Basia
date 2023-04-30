<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Repository\UserRepository;
use App\Security\TokenTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Factory\UuidFactory;

class AuthController extends ApiController
{
    public function __construct(
        private UuidFactory $uuidFactory,
        private UserRepository $userRepository
    ) {}
    #[Route('/auth', name: 'auth')]
    public function auth(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // It was already validated by ApiKeyAuthenticator
        // But it wouldn't be wrong to add a second validation later
        // $token = $request->headers->get('x-auth-token');
        $data = json_decode($request->getContent(), true);

        $email = $this->getEmail($data);
        $hashedPassword = $this->getHashedPassword($data);

        // $accessTokenEntity = $this->accessTokenRepository->findOneByToken($token);

        $user = $this->findUser($email, $hashedPassword);

        $accessToken = $this->generateAccessToken($user, $entityManager);
        
        return $this->json([
            'token' => $accessToken->getToken()
        ]);
    }

    private function generateAccessToken(User $user, EntityManagerInterface $entityManager): AccessToken
    {
        $accessToken = new AccessToken();
        $accessToken->setToken($this->generateToken());
        $accessToken->setUser($user);
        $accessToken->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($accessToken);
        $entityManager->flush();

        return $accessToken;
    }
    private function generateToken(): string
    {
        $uuid = $this->uuidFactory->create()->toHex();
        $uuid2 = $this->uuidFactory->create()->toHex();

        return $uuid . $uuid2;
    }

    private function findUser(string $email, string $password): User
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'password' => $password,
        ]);

        if (!$user) {
            throw new \Exception('User not found');
        }

        return $user;
    }

    private function getEmail(array $data): string
    {
        
        if (!array_key_exists('email', $data)) {
            throw new \Exception('Email not provided');
        }

        $email = (string) $data["email"];

        if (strlen($email) < 3) {
            throw new \Exception('Email too short');
        }

        return $email;
    }
    
    private function getHashedPassword(array $data): string
    {
        if (!array_key_exists('password', $data)) {
            throw new \Exception('Password not provided');
        }

        $password = (string) $data["password"];

        if (strlen($password) < 3) {
            throw new \Exception('Password too short');
        }

        return sha1($password);
    }
}
