<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AccessToken;
use App\Repository\AccessTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AccessTokenAuthenticator extends AbstractAuthenticator
{
    private const AUTH_PATH = '/auth';

    public function __construct(private AccessTokenRepository $accessTokenRepository) 
    {
    }
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        if ($request->getPathInfo() === self::AUTH_PATH) {
            return false;
        }
        
        return true;
        // return $request->headers->has('X-AUTH-TOKEN');
    }

    public function authenticate(Request $request): Passport
    {
        $token = (string) $request->headers->get('X-AUTH-TOKEN');

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Trying to access without authorization? Gosh, such a bad boy');
        }

        $accessToken = $this->accessTokenRepository->findOneByToken($token);

        if (!$accessToken) {
            throw new CustomUserMessageAuthenticationException('Token not found, sorry');
        }

        if (!$this->isTokenValid($accessToken)) {
            throw new CustomUserMessageAuthenticationException('Token expired, get a new one');
        }
        
        $user = $accessToken->getUser();

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Oh no, provided token is no valid :C');
        }

        return new SelfValidatingPassport(new UserBadge($user->getEmail()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        // return $request;
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function isTokenValid(AccessToken $accessToken): bool
    {
        if ($accessToken->getCreatedAt()->getTimestamp() >= strtotime("-30 days")) {
            return true;
        }

        return false;
    }
}