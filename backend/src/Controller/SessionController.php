<?php

namespace App\Controller;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;
use App\Dto\SessionDto;
use App\Http\Presenter;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/sessions')]
class SessionController extends AbstractController
{
    #[Route('', name: 'api_sessions_list', methods: ['GET'])]
    public function list(Request $request, DocumentManager $dm): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));

        $filters = array_filter([
            'language' => trim($request->query->getString('language')),
            'location' => trim($request->query->getString('location')),
        ], static fn (string $value) => '' !== $value);

        $countQuery = $dm->createQueryBuilder(TestSession::class)->count();
        $listQuery = $dm->createQueryBuilder(TestSession::class);
        foreach ($filters as $field => $value) {
            $countQuery->field($field)->equals($value);
            $listQuery->field($field)->equals($value);
        }

        $total = (int) $countQuery->getQuery()->execute();

        $sessions = iterator_to_array(
            $listQuery
                ->sort(['date' => 1, 'time' => 1])
                ->skip(($page - 1) * $limit)
                ->limit($limit)
                ->getQuery()
                ->execute(),
            false
        );

        /** @var User $user */
        $user = $this->getUser();
        $ids = array_map(static fn (TestSession $s) => $s->getId(), $sessions);
        $bookedIds = [];
        if ([] !== $ids) {
            $reservations = $dm->createQueryBuilder(Reservation::class)
                ->field('userId')->equals($user->getId())
                ->field('sessionId')->in($ids)
                ->getQuery()
                ->execute();
            foreach ($reservations as $reservation) {
                $bookedIds[$reservation->getSessionId()] = true;
            }
        }

        return $this->json([
            'items' => array_map(
                static fn (TestSession $s) => Presenter::session($s, isset($bookedIds[$s->getId()])),
                $sessions
            ),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $limit)),
        ]);
    }

    #[Route('/filters', name: 'api_sessions_filters', methods: ['GET'])]
    public function filters(DocumentManager $dm): JsonResponse
    {
        $collection = $dm->getDocumentCollection(TestSession::class);
        $languages = array_values(array_filter($collection->distinct('language'), 'is_string'));
        $locations = array_values(array_filter($collection->distinct('location'), 'is_string'));
        sort($languages, \SORT_NATURAL | \SORT_FLAG_CASE);
        sort($locations, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $this->json(['languages' => $languages, 'locations' => $locations]);
    }

    #[Route('/{id}', name: 'api_sessions_show', methods: ['GET'])]
    public function show(string $id, DocumentManager $dm): JsonResponse
    {
        return $this->json(Presenter::session($this->findOr404($dm, $id)));
    }

    #[Route('', name: 'api_sessions_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] SessionDto $dto, DocumentManager $dm): JsonResponse
    {
        $session = (new TestSession())
            ->setLanguage($dto->language)
            ->setDate($dto->date)
            ->setTime($dto->time)
            ->setLocation($dto->location)
            ->setPrice($dto->price)
            ->setPlaces($dto->places)
            ->setRemaining($dto->places);

        $dm->persist($session);
        $dm->flush();

        return $this->json(Presenter::session($session), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_sessions_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $id, #[MapRequestPayload] SessionDto $dto, DocumentManager $dm): JsonResponse
    {
        $session = $this->findOr404($dm, $id);

        $booked = $session->getPlaces() - $session->getRemaining();
        if ($dto->places < $booked) {
            throw new ConflictHttpException(sprintf('%d places sont déjà réservées.', $booked));
        }

        $session
            ->setLanguage($dto->language)
            ->setDate($dto->date)
            ->setTime($dto->time)
            ->setLocation($dto->location)
            ->setPrice($dto->price)
            ->setPlaces($dto->places)
            ->setRemaining($dto->places - $booked);
        $dm->flush();

        return $this->json(Presenter::session($session));
    }

    #[Route('/{id}', name: 'api_sessions_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id, DocumentManager $dm): Response
    {
        $session = $this->findOr404($dm, $id);

        $dm->createQueryBuilder(Reservation::class)
            ->remove()
            ->field('sessionId')->equals($id)
            ->getQuery()
            ->execute();
        $dm->remove($session);
        $dm->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function findOr404(DocumentManager $dm, string $id): TestSession
    {
        return $dm->find(TestSession::class, $id) ?? throw new NotFoundHttpException('Session introuvable.');
    }
}
