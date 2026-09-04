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

    /**
     * The recent-activity panel rendered on the user edit page (recent_activity.html.twig)
     * builds `userPath`/`rolePath` by matching an audit-log row's details against the
     * currently loaded users/roles. When the referenced user or role no longer exists
     * (deleted), no match is found and the path array stays empty/undefined. Pre-fix,
     * the template accessed `userPath[0]`/`rolePath[0]` unconditionally and threw a Twig
     * RuntimeError ("Key \"0\" does not exist as the sequence/mapping is empty"). This
     * asserts GET /s/users/edit/{id} renders successfully instead of returning a 500.
     *
     * AuditLogRepository::getLogsForUser() filters on `bundle = 'user'` AND
     * `userId = :user_id` (the acting user, not objectId), so the log's userId must be
     * the edited user's own id for it to appear in this panel at all.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataAuditLogObjectTypes')]
    public function testEditActionDoesNotCrashWhenAuditLogReferencesDeletedEntity(string $auditLogObject): void
    {
        $role = new Role();
        $role->setName('Audit Log Test Role');
        $role->setIsAdmin(false);
        $this->em->persist($role);

        $user = $this->userSetter($role);
        $this->em->persist($user);
        $this->em->flush();

        // A nonexistent id stands in for a user/role that has since been deleted. The
        // template never reads objectId directly (it matches on `details` against the
        // currently loaded users/roles), but this documents the scenario under test.
        $deletedEntityId = 999999;

        $auditLog = $this->auditLogSetter(
            $user->getId(),
            $user->getUsername(),
            'user',
            $auditLogObject,
            $deletedEntityId,
            'update',
            ['lastLogin' => ['2024-01-01 00:00:00', '2024-02-22 10:30:00']]
        );
        $this->em->persist($auditLog);
        $this->em->flush();

        $this->client->request('GET', '/s/users/edit/'.$user->getId());

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function dataAuditLogObjectTypes(): iterable
    {
        yield 'deleted user reference' => ['user'];
        yield 'deleted role reference' => ['role'];
    }
}
