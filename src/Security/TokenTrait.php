<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\AccessTokenRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\Factory\UuidFactory;

trait TokenTrait
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository
    ) {}

    public function getUserByToken(string $token): ?User
    {
        $accessTokenEntity = $this->accessTokenRepository->findOneByToken($token);
        
        return $accessTokenEntity->getUser();
    }
}