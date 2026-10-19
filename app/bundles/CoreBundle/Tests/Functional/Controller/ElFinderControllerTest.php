<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class ElFinderControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_METHOD'] ??= 'GET';
    }

    #[DataProvider('dataRoutes')]
    public function testElfinderSufficientPermissions(string $route): void
    {
        $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful('Admin user should have sufficient permissions.');
    }

    #[DataProvider('dataRoutes')]
    public function testElfinderInsufficientPermissions(string $route): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $this->client->request('GET', $route);
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(403, 'The status forbidden should be returned for users without permissions.');
        $this->assertStringContainsString('Forbidden', (string) $response->getContent());
    }

    public static function dataRoutes(): iterable
    {
        foreach (['/elfinder', '/efconnect', '/elfinder.main.js'] as $route) {
            yield $route => [$route];
        }
    }
}
