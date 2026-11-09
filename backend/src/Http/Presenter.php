<?php

namespace App\Http;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;

/** Transforme les documents en tableaux JSON (jamais de mot de passe exposé). */
final class Presenter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];
    }

    public static function session(TestSession $session, ?bool $booked = null): array
    {
        $data = [
            'id' => $session->getId(),
            'language' => $session->getLanguage(),
            'date' => $session->getDate(),
            'time' => $session->getTime(),
            'location' => $session->getLocation(),
            'price' => $session->getPrice(),
            'places' => $session->getPlaces(),
            'remaining' => $session->getRemaining(),
        ];
        if (null !== $booked) {
            $data['booked'] = $booked;
        }

        return $data;
    }

    public static function reservation(Reservation $reservation, TestSession $session): array
    {
        return [
            'id' => $reservation->getId(),
            'createdAt' => $reservation->getCreatedAt()->format(\DATE_ATOM),
            'session' => self::session($session),
        ];
    }
}
