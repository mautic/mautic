<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for OIDC subject ID handling in UserType form.
 * Tests the critical validation and linking behavior after consolidating OidcSubjectIdType into UserType.
 */
final class UserTypeOidcFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * Test that OIDC subject ID field enforces max length validation (255 chars).
     * This is a critical fix - previously values > 255 chars caused DB errors instead of validation errors.
     */
    public function testOidcSubjectIdMaxLengthValidationEnforced(): void
    {
        // Enable OIDC
        $this->configParams['open_id_is_enabled'] = 1;
        $this->setUpSymfony($this->configParams);

        // Login as admin (created by setUp)
        $admin = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($admin);

        // Create a test user to edit
        $role = new Role();
        $role->setName('Test Role');
        $this->em->persist($role);

        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        // Attempt to submit with subject ID > 255 characters
        $longSubjectId = str_repeat('a', 256);

        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);

        // Debug: check if we got to the page
        $this->assertResponseIsSuccessful('Failed to load user edit page');

        // Find the form - try different button texts
        $buttonNode = $crawler->filter('button[type="submit"]')->first();
        $this->assertCount(1, $buttonNode, 'Submit button not found on page');

        $form = $crawler->selectButton($buttonNode->text())->form();

        // Fill in the form including the too-long subject ID
        $form['user[subjectID][subjectID]'] = $longSubjectId;

        $this->client->submit($form);

        // Should NOT redirect (form should be invalid and redisplayed)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Should show validation error
        $this->assertSelectorTextContains('.has-error', 'This value is too long');

        // Verify subject ID was NOT saved to database
        $this->em->clear();

        /** @var OidcSubjectIdRepository $subjectIdRepo */
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $userId]);

        $this->assertNull($subjectId, 'Subject ID should NOT be saved when validation fails');
    }

    /**
     * Test that OIDC linking does not happen when other form fields are invalid.
     * This ensures validation runs before linking (POST_SUBMIT at priority -10 after validation at 0).
     */
    public function testOidcLinkingSkippedWhenFormInvalid(): void
    {
        // Enable OIDC
        $this->configParams['open_id_is_enabled'] = 1;
        $this->setUpSymfony($this->configParams);

        // Login as admin (created by setUp)
        $admin = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($admin);

        // Create a test user to edit
        $role = new Role();
        $role->setName('Test Role');
        $this->em->persist($role);

        $user = new User();
        $user->setUsername('testuser2');
        $user->setEmail('test2@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        // Submit with invalid email but valid OIDC subject ID
        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);
        $form    = $crawler->selectButton('Save & Close')->form();

        $form['user[email]'] = 'invalid-email'; // Invalid
        $form['user[subjectID][subjectID]'] = 'should-not-be-saved'; // Valid

        $this->client->submit($form);

        // Should NOT redirect (form invalid)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Verify subject ID was NOT saved because form was invalid
        $this->em->clear();

        /** @var OidcSubjectIdRepository $subjectIdRepo */
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $userId]);

        $this->assertNull($subjectId, 'OIDC subject ID should NOT be saved when form is invalid');
    }

    /**
     * Test that OIDC subject ID can be saved successfully when form is valid.
     */
    public function testOidcSubjectIdSavedWhenFormValid(): void
    {
        // Enable OIDC
        $this->configParams['open_id_is_enabled'] = 1;
        $this->setUpSymfony($this->configParams);

        // Login as admin (created by setUp)
        $admin = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($admin);

        // Create a test user to edit
        $role = new Role();
        $role->setName('Test Role');
        $this->em->persist($role);

        $user = new User();
        $user->setUsername('testuser3');
        $user->setEmail('test3@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        $userId = $user->getId();

        // Submit with valid data including OIDC subject ID
        $crawler = $this->client->request('GET', '/s/users/edit/'.$userId);
        $form    = $crawler->selectButton('Save & Close')->form();

        $form['user[subjectID][subjectID]'] = 'valid-oidc-id-123';

        $this->client->submit($form);

        // Should redirect to users list (success)
        $this->assertResponseIsSuccessful();
        $this->assertResponseRedirects();

        // Verify subject ID was saved
        $this->em->clear();

        /** @var OidcSubjectIdRepository $subjectIdRepo */
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $userId]);

        $this->assertNotNull($subjectId, 'OIDC subject ID should be saved when form is valid');
        $this->assertSame('valid-oidc-id-123', $subjectId->getSubjectID());
    }
}
