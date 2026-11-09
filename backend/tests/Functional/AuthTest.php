<?php

namespace App\Tests\Functional;

class AuthTest extends ApiTestCase
{
    public function testRegisterReturnsTokenAndUser(): void
    {
        $this->request('POST', '/api/register', ['name' => 'Alice', 'email' => 'Alice@Example.com', 'password' => 'Password123']);

        self::assertResponseStatusCodeSame(201);
        $body = $this->body();
        self::assertNotEmpty($body['token']);
        self::assertSame('alice@example.com', $body['user']['email']);
        self::assertArrayNotHasKey('password', $body['user']);
    }

    public function testRegisterRejectsInvalidData(): void
    {
        $this->request('POST', '/api/register', ['name' => '', 'email' => 'not-an-email', 'password' => 'short']);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('email', $this->body()['violations']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $payload = ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'Password123'];
        $this->request('POST', '/api/register', $payload);
        $this->request('POST', '/api/register', $payload);

        self::assertResponseStatusCodeSame(409);
    }

    public function testLoginWithBadPasswordFails(): void
    {
        $this->createUserAndLogin('bob@example.com');
        $this->request('POST', '/api/login', ['email' => 'bob@example.com', 'password' => 'wrong']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRequiresToken(): void
    {
        $this->request('GET', '/api/me');
        self::assertResponseStatusCodeSame(401);

        $this->request('GET', '/api/sessions');
        self::assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsCurrentUser(): void
    {
        $token = $this->createUserAndLogin('carol@example.com');
        $this->request('GET', '/api/me', null, $token);

        self::assertResponseIsSuccessful();
        self::assertSame('carol@example.com', $this->body()['email']);
    }

    public function testUpdateProfileReturnsNewToken(): void
    {
        $token = $this->createUserAndLogin('dave@example.com');
        $this->request('PUT', '/api/me', ['name' => 'Dave 2', 'email' => 'dave2@example.com'], $token);

        self::assertResponseIsSuccessful();
        $body = $this->body();
        self::assertSame('Dave 2', $body['user']['name']);
        self::assertSame('dave2@example.com', $body['user']['email']);

        $this->request('GET', '/api/me', null, $body['token']);
        self::assertResponseIsSuccessful();
        self::assertSame('dave2@example.com', $this->body()['email']);
    }

    public function testUpdateProfileRejectsEmailOfAnotherUser(): void
    {
        $this->createUserAndLogin('taken@example.com');
        $token = $this->createUserAndLogin('eve@example.com');
        $this->request('PUT', '/api/me', ['name' => 'Eve', 'email' => 'taken@example.com'], $token);

        self::assertResponseStatusCodeSame(409);
    }
}
