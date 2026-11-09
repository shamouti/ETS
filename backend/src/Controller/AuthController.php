<?php

namespace App\Controller;

use App\Document\User;
use App\Dto\RegisterDto;
use App\Http\Presenter;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterDto $dto,
        DocumentManager $dm,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwt,
    ): JsonResponse {
        $email = mb_strtolower(trim($dto->email));

        if (null !== $dm->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new ConflictHttpException('Un compte existe déjà avec cet email.');
        }

        $user = (new User())
            ->setName(trim($dto->name))
            ->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $dto->password));

        $dm->persist($user);
        $dm->flush();

        return $this->json([
            'token' => $jwt->create($user),
            'user' => Presenter::user($user),
        ], Response::HTTP_CREATED);
    }

    /** Interceptée par le firewall "login" (json_login) : renvoie {"token": "..."}. */
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Cette route est gérée par le firewall.');
    }
}
