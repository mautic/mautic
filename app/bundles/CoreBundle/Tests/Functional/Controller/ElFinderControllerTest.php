<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;

final class ElFinderControllerTest extends MauticMysqlTestCase
{
    public function testElfinderSufficientPermissions(): void
    {
        $this->client->request('GET', '/elfinder');
        $this->assertResponseIsSuccessful('Admin user should have sufficient permissions.');
    }

    public function testElfinderInsufficientPermissions(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $this->client->request('GET', '/elfinder');
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(403, 'The status forbidden should be returned for users without permissions.');
        $this->assertStringContainsString('Forbidden', (string) $response->getContent());
    }
}
