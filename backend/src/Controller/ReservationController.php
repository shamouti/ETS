<?php

namespace App\Controller;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;
use App\Http\Presenter;
use App\Service\ReservationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ReservationController extends AbstractController
{
    #[Route('/sessions/{id}/reservations', name: 'api_reservations_create', methods: ['POST'])]
    public function reserve(string $id, ReservationService $service, DocumentManager $dm): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $reservation = $service->reserve($user, $id);
        $session = $dm->find(TestSession::class, $id);

        return $this->json(Presenter::reservation($reservation, $session), Response::HTTP_CREATED);
    }

    #[Route('/reservations', name: 'api_reservations_list', methods: ['GET'])]
    public function list(DocumentManager $dm): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Reservation[] $reservations */
        $reservations = iterator_to_array(
            $dm->createQueryBuilder(Reservation::class)
                ->field('userId')->equals($user->getId())
                ->sort('createdAt', -1)
                ->getQuery()
                ->execute(),
            false
        );

        $sessions = [];
        if ([] !== $reservations) {
            $found = $dm->createQueryBuilder(TestSession::class)
                ->field('id')->in(array_map(static fn (Reservation $r) => $r->getSessionId(), $reservations))
                ->getQuery()
                ->execute();
            foreach ($found as $session) {
                $sessions[$session->getId()] = $session;
            }
        }

        $items = [];
        foreach ($reservations as $reservation) {
            if (isset($sessions[$reservation->getSessionId()])) {
                $items[] = Presenter::reservation($reservation, $sessions[$reservation->getSessionId()]);
            }
        }

        return $this->json($items);
    }

    #[Route('/reservations/{id}', name: 'api_reservations_cancel', methods: ['DELETE'])]
    public function cancel(string $id, ReservationService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $service->cancel($user, $id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
