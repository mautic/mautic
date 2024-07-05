<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testEditGetPage(): void
    {
        $this->client->request('GET', '/s/users/edit/1');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testRedirectNonExistingUser(): void
    {
        $crawler = $this->client->request('GET', '/s/users/edit/00000');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Users', $crawler->filter('h1')->text());
        $this->assertStringContainsString('User not found with', $crawler->filter('#flashes')->text());
    }

    public function testEditActionFormSubmissionValid(): void
    {
        $crawler                = $this->client->request('GET', '/s/users/edit/1');
        $buttonCrawlerNode      = $crawler->selectButton('Save & Close');
        $form                   = $buttonCrawlerNode->form();
        $form['user[username]'] = 'test';
        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('has been updated!', $response->getContent());
    }

    public function testEditActionFormSubmissionInvalid(): void
    {
        $crawler = $this->client->request('GET', '/s/users/edit/1');

        $form = $crawler->selectButton('Save')->form([
            'user[firstName]'               => '',
            'user[lastName]'                => '',
            'user[email]'                   => 'invalid-email',
            'user[plainPassword][password]' => '',
        ]);

        $this->client->submit($form);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('The email entered is invalid.', $this->client->getResponse()->getContent());
    }

    /**
     * @param array<mixed> $details
     */
    public function auditLogSetter(
        int $userId,
        string $userName,
        string $bundle,
        string $object,
        int $objectId,
        string $action,
        array $details
    ): AuditLog {
        $auditLog = new AuditLog();
        $auditLog->setUserId($userId);
        $auditLog->setUserName($userName);
        $auditLog->setBundle($bundle);
        $auditLog->setObject($object);
        $auditLog->setObjectId($objectId);
        $auditLog->setAction($action);
        $auditLog->setDetails($details);
        $auditLog->setDateAdded(new \DateTime());
        $auditLog->setIpAddress('127.0.0.1');

        return $auditLog;
    }

    public function userSetter(Role $role): User
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@email.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $user->setLastLogin('2024-02-22 10:30:00');

        return $user;
    }
}
