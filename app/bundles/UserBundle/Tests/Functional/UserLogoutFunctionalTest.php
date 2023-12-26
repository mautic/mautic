<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserLogoutFunctionalTest extends MauticMysqlTestCase
{
    public function testLogout(): void
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin(true);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $user->setRole($role);
        $encoder = self::$container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('mautic', null));
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        // Login newly created non-admin user
        $this->loginUser($user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

        $this->client->request(Request::METHOD_GET, '/s/logout');
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString(
            'login',
            $clientResponse->getContent()
        );
    }
}
