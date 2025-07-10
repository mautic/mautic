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
        $crawler                 = $this->client->request('GET', '/s/users/edit/1');
        $buttonCrawlerNode       = $crawler->selectButton('Save & Close');
        $form                    = $buttonCrawlerNode->form();
        $form['user[firstName]'] = 'test';
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
     * @param array<string, string> $data
     *
     * @dataProvider dataNewUserForPasswordField
     */
    public function testNewUserForPasswordField(array $data, string $message): void
    {
        $crawler = $this->client->request('GET', '/s/users/new');

        $formData = [
            'user[firstName]' => 'John',
            'user[lastName]'  => 'Doe',
            'user[email]'     => 'john.doe@example.com',
        ];

        $form = $crawler->selectButton('Save')->form($formData + $data);

        $this->client->submit($form);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($message, $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public function dataNewUserForPasswordField(): iterable
    {
        yield 'Blank' => [
            [
                'user[plainPassword][password]' => '',
                'user[plainPassword][confirm]'  => '',
            ],
            'Password cannot be blank.',
        ];

        yield 'Do not match with confirm' => [
            [
                'user[plainPassword][password]' => 'same',
            ],
            'Passwords do not match.',
        ];

        yield 'Minimum length' => [
            [
                'user[plainPassword][password]' => 'same',
                'user[plainPassword][confirm]'  => 'same',
            ],
            'Password must be at least 6 characters.',
        ];

        yield 'No stronger' => [
            [
                'user[plainPassword][password]' => 'same123',
                'user[plainPassword][confirm]'  => 'same123',
            ],
            'Please enter a stronger password. Your password must use a combination of upper and lower case, special characters and numbers.',
        ];
    }

    /**
     * @param array<string, string> $data
     *
     * @dataProvider dataForEditUserForPasswordField
     */
    public function testEditUserForPasswordField(array $data, string $message): void
    {
        $crawler = $this->client->request('GET', '/s/users/edit/1');

        $form = $crawler->selectButton('Save')->form($data);

        $this->client->submit($form);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($message, $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public function dataForEditUserForPasswordField(): iterable
    {
        yield 'Do not match with confirm' => [
            [
                'user[plainPassword][password]' => 'same',
            ],
            'Passwords do not match.',
        ];

        yield 'Minimum length' => [
            [
                'user[plainPassword][password]' => 'same',
                'user[plainPassword][confirm]'  => 'same',
            ],
            'Password must be at least 6 characters.',
        ];

        yield 'No stronger' => [
            [
                'user[plainPassword][password]' => 'same123',
                'user[plainPassword][confirm]'  => 'same123',
            ],
            'Please enter a stronger password. Your password must use a combination of upper and lower case, special characters and numbers.',
        ];
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
        array $details,
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
