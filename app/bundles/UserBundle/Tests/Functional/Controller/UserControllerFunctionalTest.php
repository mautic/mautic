<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class UserControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams += [
            'saml_idp_own_private_key' => 'any_string',
        ];
        parent::setUp();
    }

    public function testEditGetPage(): void
    {
        $this->client->request('GET', '/s/users/edit/1');
        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('unmatchedActivityProvider')]
    public function testEditPageWithUnmatchedActivity(string $object, string $action): void
    {
        // Non-empty profile values prevent unrelated users matching empty audit details.
        foreach ($this->em->getRepository(User::class)->findAll() as $user) {
            $user->setFirstName('Existing');
            $user->setLastName('User');
            $user->setPosition('Existing position');
            $user->setSignature('Existing signature');
        }
        $role = new Role();
        $role->setName('Activity test role');
        $role->setDescription('Activity test role description');
        $this->em->persist($role);

        $actor = $this->userSetter($role);
        $actor->setPosition('Activity test position');
        $actor->setSignature('Activity test signature');
        $this->em->persist($actor);
        $this->em->flush();

        $log = $this->auditLogSetter($actor->getId(), 'Historical actor', 'user', $object, 999999, $action, [
            'email'    => ['', 'deleted@example.com'],
            // Older records may lack a usable username as well as a current target.
            'username' => ['', ''],
            'name'     => ['', 'Deleted role'],
        ]);
        $this->em->persist($log);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/s/users/edit/'.$actor->getId());

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form[name="user"]'));
        $activity = $crawler->filter('.media-list-feed li.media');
        $this->assertCount(1, $activity);
        $this->assertCount(1, $activity->filter('a'));
        $this->assertSame('Historical actor', $activity->filter('a')->text());
        $this->assertSame('/s/users/edit/'.$actor->getId(), $activity->filter('a')->attr('href'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unmatchedActivityProvider(): iterable
    {
        yield 'user created' => ['user', 'create'];
        yield 'user updated' => ['user', 'update'];
    }

    public function testRedirectNonExistingUser(): void
    {
        $crawler = $this->client->request('GET', '/s/users/edit/00000');
        $this->assertResponseIsSuccessful();
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
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('has been updated!', (string) $response->getContent());
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('The email entered is invalid.', (string) $this->client->getResponse()->getContent());
    }

    public function testIndexIncludesInviteForm(): void
    {
        $crawler = $this->client->request('GET', '/s/users');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('#invite-user-form')->count());
    }

    public function testInviteActionShowsForm(): void
    {
        $crawler = $this->client->request('GET', '/s/users/invite');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('#invite-user-form')->count());
    }

    public function testInviteActionReturnsInvalidForm(): void
    {
        $this->client->request('POST', '/s/users/invite');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('name="user_invite"', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('dataNewUserForPasswordField')]
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($message, (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public static function dataNewUserForPasswordField(): iterable
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
     */
    #[DataProvider('dataForEditUserForPasswordField')]
    public function testEditUserForPasswordField(array $data, string $message): void
    {
        $crawler = $this->client->request('GET', '/s/users/edit/1');

        $form = $crawler->selectButton('Save')->form($data);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($message, (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public static function dataForEditUserForPasswordField(): iterable
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
