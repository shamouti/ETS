<?php

namespace App\Controller;

use App\Document\User;
use App\Dto\UpdateProfileDto;
use App\Http\Presenter;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(Presenter::user($user));
    }

    /**
     * Le JWT contient l'email : en cas de changement d'email, un nouveau token est renvoyé.
     */
    #[Route('', name: 'api_me_update', methods: ['PUT'])]
    public function update(
        #[MapRequestPayload] UpdateProfileDto $dto,
        DocumentManager $dm,
        JWTTokenManagerInterface $jwt,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $email = mb_strtolower(trim($dto->email));

        $other = $dm->getRepository(User::class)->findOneBy(['email' => $email]);
        if (null !== $other && $other->getId() !== $user->getId()) {
            throw new ConflictHttpException('Cet email est déjà utilisé.');
        }

        $user->setName(trim($dto->name))->setEmail($email);
        $dm->flush();

        return $this->json([
            'token' => $jwt->create($user),
            'user' => Presenter::user($user),
        ]);
    }
}
