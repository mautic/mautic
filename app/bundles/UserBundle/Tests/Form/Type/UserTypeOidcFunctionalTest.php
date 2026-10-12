<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;

/**
 * Functional tests for OIDC subject ID handling in UserType form.
 * Tests the critical validation and linking behavior after consolidating OidcSubjectIdType into UserType.
 */
final class UserTypeOidcFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['open_id_is_enabled'] = 1;
        parent::setUp();
    }

    /**
     * Test that OIDC subject ID field enforces max length validation (255 chars).
     * This is a critical fix - previously values > 255 chars caused DB errors instead of validation errors.
     */
    public function testOidcSubjectIdMaxLengthValidationEnforced(): void
    {
        $userId = $this->createTestUser('testuser', 'test@example.com');

        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);
        $form    = $crawler->selectButton('Save & Close')->form();

        $form['user[subjectID][subjectID]'] = str_repeat('a', 256);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('This value is too long', (string) $this->client->getResponse()->getContent());

        $this->em->clear();
        $subjectId = $this->em->getRepository(OidcSubjectId::class)->findOneBy(['user' => $userId]);

        $this->assertNull($subjectId, 'Subject ID should NOT be saved when validation fails');
    }

    /**
     * Test that OIDC linking does not happen when other form fields are invalid.
     * This ensures validation runs before linking (POST_SUBMIT at priority -10 after validation at 0).
     */
    public function testOidcLinkingSkippedWhenFormInvalid(): void
    {
        $userId = $this->createTestUser('testuser2', 'test2@example.com');

        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);
        $form    = $crawler->selectButton('Save & Close')->form();

        $form['user[email]']               = 'invalid-email';
        $form['user[subjectID][subjectID]'] = 'should-not-be-saved';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('The email entered is invalid', (string) $this->client->getResponse()->getContent());

        $this->em->clear();
        $subjectId = $this->em->getRepository(OidcSubjectId::class)->findOneBy(['user' => $userId]);

        $this->assertNull($subjectId, 'OIDC subject ID should NOT be saved when form is invalid');
    }

    /**
     * Test that OIDC subject ID can be saved successfully when form is valid.
     */
    public function testOidcSubjectIdSavedWhenFormValid(): void
    {
        $userId = $this->createTestUser('testuser3', 'test3@example.com');

        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);
        $form    = $crawler->selectButton('Save & Close')->form();

        $form['user[subjectID][subjectID]'] = 'valid-oidc-id-123';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('has been updated!', (string) $this->client->getResponse()->getContent());

        $this->em->clear();

        $subjectId = $this->em->getRepository(OidcSubjectId::class)->findOneBy(['user' => $userId]);

        $this->assertNotNull($subjectId, 'OIDC subject ID should be saved when form is valid');
        $this->assertSame('valid-oidc-id-123', $subjectId->getSubjectID());
    }

    private function createTestUser(string $username, string $email): int
    {
        $role = new Role();
        $role->setName('Test Role '.$username);
        $this->em->persist($role);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        return $user->getId();
    }
}
