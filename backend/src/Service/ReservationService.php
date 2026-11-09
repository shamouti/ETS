<?php

namespace App\Service;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReservationService
{
    public function __construct(private readonly DocumentManager $dm)
    {
    }

    public function reserve(User $user, string $sessionId): Reservation
    {
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('Un administrateur ne peut pas réserver de session.');
        }

        $session = $this->dm->find(TestSession::class, $sessionId)
            ?? throw new NotFoundHttpException('Session introuvable.');

        $existing = $this->dm->getRepository(Reservation::class)
            ->findOneBy(['userId' => $user->getId(), 'sessionId' => $sessionId]);
        if (null !== $existing) {
            throw new ConflictHttpException('Vous avez déjà réservé cette session.');
        }

        // Décrément atomique conditionnel : évite le surbooking en cas d'accès concurrents.
        $result = $this->dm->createQueryBuilder(TestSession::class)
            ->updateOne()
            ->field('remaining')->inc(-1)
            ->field('id')->equals($sessionId)
            ->field('remaining')->gt(0)
            ->getQuery()
            ->execute();

        if (1 !== $result->getModifiedCount()) {
            throw new ConflictHttpException('Cette session est complète.');
        }

        $reservation = new Reservation($user->getId(), $sessionId);
        try {
            $this->dm->persist($reservation);
            $this->dm->flush();
        } catch (\Throwable $e) {
            $this->adjustRemaining($sessionId, 1);
            if (11000 === $e->getCode() || str_contains($e->getMessage(), 'E11000')) {
                throw new ConflictHttpException('Vous avez déjà réservé cette session.');
            }
            throw $e;
        }

        $this->dm->refresh($session);

        return $reservation;
    }

    public function cancel(User $user, string $reservationId): void
    {
        $reservation = $this->dm->find(Reservation::class, $reservationId)
            ?? throw new NotFoundHttpException('Réservation introuvable.');

        if ($reservation->getUserId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Cette réservation ne vous appartient pas.');
        }

        $sessionId = $reservation->getSessionId();
        $this->dm->remove($reservation);
        $this->dm->flush();
        $this->adjustRemaining($sessionId, 1);
    }

    private function adjustRemaining(string $sessionId, int $delta): void
    {
        $this->dm->createQueryBuilder(TestSession::class)
            ->updateOne()
            ->field('remaining')->inc($delta)
            ->field('id')->equals($sessionId)
            ->getQuery()
            ->execute();
    }
}
