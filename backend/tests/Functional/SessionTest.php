<?php

namespace App\Tests\Functional;

class SessionTest extends ApiTestCase
{
    private const PAYLOAD = ['language' => 'Anglais', 'date' => '2030-05-01', 'time' => '10:30', 'location' => 'Paris', 'places' => 10, 'price' => 95.5];

    public function testListIsPaginated(): void
    {
        $token = $this->createUserAndLogin();
        for ($i = 0; $i < 12; ++$i) {
            $this->createSession();
        }

        $this->request('GET', '/api/sessions?page=2&limit=5', null, $token);

        self::assertResponseIsSuccessful();
        $body = $this->body();
        self::assertCount(5, $body['items']);
        self::assertSame(12, $body['total']);
        self::assertSame(3, $body['pages']);
        self::assertSame(2, $body['page']);
    }

    public function testListCanBeFilteredByLanguageAndLocation(): void
    {
        $token = $this->createUserAndLogin();
        $admin = $this->createUserAndLogin('admin@example.com', true);
        foreach ([['Anglais', 'Paris'], ['Anglais', 'Lyon'], ['Italien', 'Paris']] as [$language, $location]) {
            $this->request('POST', '/api/sessions', [...self::PAYLOAD, 'language' => $language, 'location' => $location], $admin);
            self::assertResponseStatusCodeSame(201);
        }

        $this->request('GET', '/api/sessions?language=Anglais', null, $token);
        self::assertSame(2, $this->body()['total']);

        $this->request('GET', '/api/sessions?location=Paris', null, $token);
        self::assertSame(2, $this->body()['total']);

        $this->request('GET', '/api/sessions?language=Anglais&location=Paris', null, $token);
        self::assertSame(1, $this->body()['total']);
        self::assertCount(1, $this->body()['items']);

        $this->request('GET', '/api/sessions?language=Klingon', null, $token);
        self::assertSame(0, $this->body()['total']);
        self::assertSame(1, $this->body()['pages']);
    }

    public function testFiltersListsDistinctValues(): void
    {
        $token = $this->createUserAndLogin();
        $admin = $this->createUserAndLogin('admin@example.com', true);
        foreach ([['Italien', 'Rome'], ['Anglais', 'Paris'], ['Anglais', 'Lyon']] as [$language, $location]) {
            $this->request('POST', '/api/sessions', [...self::PAYLOAD, 'language' => $language, 'location' => $location], $admin);
        }

        $this->request('GET', '/api/sessions/filters', null, $token);

        self::assertResponseIsSuccessful();
        self::assertSame(['Anglais', 'Italien'], $this->body()['languages']);
        self::assertSame(['Lyon', 'Paris', 'Rome'], $this->body()['locations']);
    }

    public function testRegularUserCannotCreateSession(): void
    {
        $token = $this->createUserAndLogin();
        $this->request('POST', '/api/sessions', self::PAYLOAD, $token);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCrud(): void
    {
        $token = $this->createUserAndLogin('admin@example.com', true);

        $this->request('POST', '/api/sessions', self::PAYLOAD, $token);
        self::assertResponseStatusCodeSame(201);
        $id = $this->body()['id'];
        self::assertSame(10, $this->body()['remaining']);
        self::assertSame(95.5, $this->body()['price']);

        $this->request('PUT', "/api/sessions/$id", [...self::PAYLOAD, 'places' => 20, 'location' => 'Lyon', 'price' => 120], $token);
        self::assertResponseIsSuccessful();
        self::assertSame(20, $this->body()['remaining']);
        self::assertEquals(120, $this->body()['price']);
        self::assertSame('Lyon', $this->body()['location']);

        $this->request('GET', "/api/sessions/$id", null, $token);
        self::assertResponseIsSuccessful();

        $this->request('DELETE', "/api/sessions/$id", null, $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/api/sessions/$id", null, $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateValidatesPayload(): void
    {
        $token = $this->createUserAndLogin('admin@example.com', true);
        $this->request('POST', '/api/sessions', ['language' => '', 'date' => 'nope', 'time' => '99:99', 'location' => '', 'places' => 0, 'price' => -5], $token);

        self::assertResponseStatusCodeSame(422);
        $violations = $this->body()['violations'];
        self::assertArrayHasKey('date', $violations);
        self::assertArrayHasKey('time', $violations);
        self::assertArrayHasKey('places', $violations);
        self::assertArrayHasKey('price', $violations);
    }
}
