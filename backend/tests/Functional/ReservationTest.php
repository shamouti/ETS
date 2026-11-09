<?php

namespace App\Tests\Functional;

class ReservationTest extends ApiTestCase
{
    public function testUserCanReserveAndSeeReservation(): void
    {
        $token = $this->createUserAndLogin();
        $sessionId = $this->createSession(3);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $token);
        self::assertResponseStatusCodeSame(201);
        self::assertSame(2, $this->body()['session']['remaining']);

        $this->request('GET', '/api/reservations', null, $token);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->body());
        self::assertSame($sessionId, $this->body()[0]['session']['id']);

        $this->request('GET', '/api/sessions', null, $token);
        self::assertTrue($this->body()['items'][0]['booked']);
    }

    public function testCannotReserveSameSessionTwice(): void
    {
        $token = $this->createUserAndLogin();
        $sessionId = $this->createSession(3);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $token);
        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $token);

        self::assertResponseStatusCodeSame(409);

        $this->request('GET', "/api/sessions/$sessionId", null, $token);
        self::assertSame(2, $this->body()['remaining']);
    }

    public function testCannotReserveFullSession(): void
    {
        $first = $this->createUserAndLogin('first@example.com');
        $second = $this->createUserAndLogin('second@example.com');
        $sessionId = $this->createSession(1);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $first);
        self::assertResponseStatusCodeSame(201);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $second);
        self::assertResponseStatusCodeSame(409);
    }

    public function testAdminCannotReserve(): void
    {
        $admin = $this->createUserAndLogin('admin@example.com', true);
        $sessionId = $this->createSession(3);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $admin);
        self::assertResponseStatusCodeSame(403);

        $this->request('GET', "/api/sessions/$sessionId", null, $admin);
        self::assertSame(3, $this->body()['remaining']);
    }

    public function testReserveUnknownSessionReturns404(): void
    {
        $token = $this->createUserAndLogin();
        $this->request('POST', '/api/sessions/000000000000000000000000/reservations', null, $token);

        self::assertResponseStatusCodeSame(404);
    }

    public function testCancelFreesPlace(): void
    {
        $token = $this->createUserAndLogin();
        $sessionId = $this->createSession(1);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $token);
        $reservationId = $this->body()['id'];

        $this->request('DELETE', "/api/reservations/$reservationId", null, $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/api/sessions/$sessionId", null, $token);
        self::assertSame(1, $this->body()['remaining']);

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $token);
        self::assertResponseStatusCodeSame(201);
    }

    public function testCannotCancelSomeoneElsesReservation(): void
    {
        $owner = $this->createUserAndLogin('owner@example.com');
        $other = $this->createUserAndLogin('other@example.com');
        $sessionId = $this->createSession();

        $this->request('POST', "/api/sessions/$sessionId/reservations", null, $owner);
        $reservationId = $this->body()['id'];

        $this->request('DELETE', "/api/reservations/$reservationId", null, $other);
        self::assertResponseStatusCodeSame(403);

        $this->request('GET', '/api/reservations', null, $other);
        self::assertCount(0, $this->body());
    }
}
