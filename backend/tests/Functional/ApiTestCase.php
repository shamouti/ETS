<?php

namespace App\Tests\Functional;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected DocumentManager $dm;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get(DocumentManager::class);

        foreach ([User::class, TestSession::class, Reservation::class] as $class) {
            $this->dm->getDocumentCollection($class)->deleteMany([]);
        }
        $this->dm->clear();
        $this->dm->getSchemaManager()->ensureIndexes();
    }

    /** Crée un utilisateur en base et retourne son JWT obtenu via /api/login. */
    protected function createUserAndLogin(string $email = 'user@example.com', bool $admin = false): string
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = (new User())->setName('Test')->setEmail($email)->setRoles($admin ? ['ROLE_ADMIN'] : []);
        $user->setPassword($hasher->hashPassword($user, 'Password123'));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->request('POST', '/api/login', ['email' => $email, 'password' => 'Password123']);
        self::assertResponseIsSuccessful();

        return $this->body()['token'];
    }

    protected function createSession(int $places = 5): string
    {
        $session = (new TestSession())
            ->setLanguage('Anglais')->setDate('2030-01-15')->setTime('09:00')
            ->setLocation('Paris')->setPlaces($places)->setRemaining($places);
        $this->dm->persist($session);
        $this->dm->flush();

        return $session->getId();
    }

    protected function request(string $method, string $uri, ?array $json = null, ?string $token = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $this->client->request($method, $uri, [], [], $server, null !== $json ? json_encode($json) : null);
    }

    protected function body(): array
    {
        return json_decode($this->client->getResponse()->getContent() ?: '[]', true) ?? [];
    }
}
