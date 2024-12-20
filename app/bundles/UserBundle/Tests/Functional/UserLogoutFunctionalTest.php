<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

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
        $hasher = static::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        // Login newly created non-admin user
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_GET, '/s/logout');
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString(
            'login',
            $clientResponse->getContent()
        );
    }
}
