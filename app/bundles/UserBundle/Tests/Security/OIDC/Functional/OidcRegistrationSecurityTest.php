<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Tests\Security\OIDC\Double\Service\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This test verifies that OIDC self-registration cannot be exploited for account takeover.
 *
 * Specifically, it tests that an attacker cannot:
 * 1. Set their preferred_username to match an existing user's email
 * 2. Set their email to match an existing user's username
 *
 * Both scenarios should result in either a separate new user being created or registration failure,
 * never linking the attacker's OIDC identity to the existing user.
 */
final class OidcRegistrationSecurityTest extends MauticMysqlTestCase
{
    private const LOGIN_PATH = '/s/open_id/login';

    protected function setUp(): void
    {
        // Reset the test double client state before parent setUp
        Client::reset();

        parent::setUp();
        // Clear automatic login from parent - OIDC tests need explicit control over auth state
        $this->logOut();

        $role = new Role();
        $role->setName('User');
        $role->setIsAdmin(false);
        $this->em->persist($role);
        $this->em->flush();

        // Create a victim user that the attacker will try to take over
        $victimUser = new User();
        $victimUser->setUsername('victim');
        $victimUser->setPassword('victim_password');
        $victimUser->setFirstName('Victim');
        $victimUser->setLastName('User');
        $victimUser->setEmail('victim@example.com');
        $victimUser->setRole($role);
        $this->em->persist($victimUser);

        $this->em->flush();
    }

    /**
     * Test that an attacker cannot take over an account by setting preferred_username
     * to match the victim's email address.
     *
     * Attack scenario:
     * 1. Attacker controls their IdP account (e.g., Keycloak with self-registration)
     * 2. Attacker sets preferred_username = "victim@example.com" (the victim's Mautic email)
     * 3. Attacker keeps their own unused email
     * 4. Attacker attempts to log in through OIDC
     *
     * Expected: Registration should fail or create a separate new user, never link to victim.
     */
    public function testPreferredUsernameMatchingExistingEmailDoesNotTakeoverAccount(): void
    {
        $userRepo = $this->em->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);

        // Verify victim user exists
        $victimUser = $userRepo->findOneBy(['email' => 'victim@example.com']);
        $this->assertInstanceOf(User::class, $victimUser);
        $victimUserId = $victimUser->getId();

        // Configure the test double with attacker data
        // The attacker sets preferred_username to the victim's email
        Client::$userInfoResponse = [
            'sub'                => 'attacker_sub_123',
            'email'              => 'attacker@evil.com',  // Attacker's own email
            'preferred_username' => 'victim@example.com',  // Victim's email!
            'given_name'         => 'Attacker',
            'family_name'        => 'Evil',
        ];
        Client::$verifiedClaimsResponse = Client::$userInfoResponse;
        Client::$mappingFieldResponse = 'sub';
        Client::$authenticateResponse = new RedirectResponse('https://oidc-provider.example.com');

        // Enable OIDC with user registration allowed
        $role = $this->em->getRepository(Role::class)->findOneBy(['name' => 'User']);
        $parameters = (new ParametersBuilder())
            ->withIsUserRegistrationAllowed(true)
            ->withRegisteredUserRoleId($role->getId())
            ->build();
        $this->client->getContainer()->set(Settings::class, $parameters);

        $this->client->followRedirects();
        $this->client->setMaxRedirects(10);
        $this->client->disableReboot();

        // Attempt OIDC login/registration - should fail
        $this->client->request('GET', self::LOGIN_PATH, [
            'code'  => 'test_code',
            'state' => 'test_state',
        ]);

        // Registration should have failed - verify no OIDC link was created for attacker
        $oidcRepo = $this->em->getRepository(\Mautic\UserBundle\Entity\OidcSubjectId::class);
        $attackerOidcLink = $oidcRepo->findOneBy(['subjectID' => 'attacker_sub_123']);
        $this->assertNull($attackerOidcLink, 'Attacker OIDC link should not exist - registration should have been rejected');

        // Verify no user was created with attacker's email
        $attackerUser = $userRepo->findOneBy(['email' => 'attacker@evil.com']);
        $this->assertNull($attackerUser, 'No user should have been created with attacker email');

        // Verify the victim user was NOT modified
        $this->em->clear();
        $victimUserAfter = $userRepo->find($victimUserId);
        $this->assertInstanceOf(User::class, $victimUserAfter);
        $this->assertEquals('victim@example.com', $victimUserAfter->getEmail());
        $this->assertEquals('victim', $victimUserAfter->getUsername());

        // Verify the victim is NOT linked to the attacker's OIDC sub
        $victimOidcLink = $oidcRepo->findOneBy(['user' => $victimUserId]);
        $this->assertNull($victimOidcLink, 'Victim should not have any OIDC link');
    }

    /**
     * Test variant 2: The attacker's email matches the victim's username,
     * and preferred_username is already taken.
     *
     * Attack scenario:
     * 1. Attacker sets email = "victim" (matches victim's username)
     * 2. Attacker sets preferred_username to something already taken
     * 3. UserFactory falls back to using email as username
     * 4. findUser() could match the victim by username
     *
     * Expected: Registration should fail or create separate user, never link to victim.
     */
    public function testEmailMatchingExistingUsernameDoesNotTakeoverAccount(): void
    {
        $userRepo = $this->em->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);

        // Create another user to occupy a preferred_username
        $otherRole = $this->em->getRepository(Role::class)->findOneBy(['name' => 'User']);
        $otherUser = new User();
        $otherUser->setUsername('taken_username');
        $otherUser->setPassword('password');
        $otherUser->setFirstName('Other');
        $otherUser->setLastName('User');
        $otherUser->setEmail('other@example.com');
        $otherUser->setRole($otherRole);
        $this->em->persist($otherUser);
        $this->em->flush();

        // Verify victim user exists
        $victimUser = $userRepo->findOneBy(['username' => 'victim']);
        $this->assertInstanceOf(User::class, $victimUser);
        $victimUserId = $victimUser->getId();

        // Configure the test double with attacker data
        // The attacker sets email to match victim's username
        Client::$userInfoResponse = [
            'sub'                => 'attacker_sub_456',
            'email'              => 'victim',  // Matches victim's username!
            'preferred_username' => 'taken_username',  // Already taken, will fallback to email
            'given_name'         => 'Attacker',
            'family_name'        => 'Evil2',
        ];
        Client::$verifiedClaimsResponse = Client::$userInfoResponse;
        Client::$mappingFieldResponse = 'sub';
        Client::$authenticateResponse = new RedirectResponse('https://oidc-provider.example.com');

        // Enable OIDC with user registration allowed
        $role = $this->em->getRepository(Role::class)->findOneBy(['name' => 'User']);
        $parameters = (new ParametersBuilder())
            ->withIsUserRegistrationAllowed(true)
            ->withRegisteredUserRoleId($role->getId())
            ->build();
        $this->client->getContainer()->set(Settings::class, $parameters);

        $this->client->followRedirects();
        $this->client->setMaxRedirects(10);
        $this->client->disableReboot();

        // Attempt OIDC login/registration - should fail
        $this->client->request('GET', self::LOGIN_PATH, [
            'code'  => 'test_code',
            'state' => 'test_state',
        ]);

        // Registration should have failed - verify no OIDC link was created for attacker
        $oidcRepo = $this->em->getRepository(\Mautic\UserBundle\Entity\OidcSubjectId::class);
        $attackerOidcLink = $oidcRepo->findOneBy(['subjectID' => 'attacker_sub_456']);
        $this->assertNull($attackerOidcLink, 'Attacker OIDC link should not exist - registration should have been rejected');

        // Verify no user was created with attacker's invalid email (which matches victim username)
        $attackerUser = $userRepo->findOneBy(['email' => 'victim']);
        $this->assertNull($attackerUser, 'No user should have been created with the collision-causing email');

        // Verify the victim user was NOT modified
        $this->em->clear();
        $victimUserAfter = $userRepo->find($victimUserId);
        $this->assertInstanceOf(User::class, $victimUserAfter);
        $this->assertEquals('victim@example.com', $victimUserAfter->getEmail());
        $this->assertEquals('victim', $victimUserAfter->getUsername());

        // Verify the victim is NOT linked to the attacker's OIDC sub
        $victimOidcLink = $oidcRepo->findOneBy(['user' => $victimUserId]);
        $this->assertNull($victimOidcLink, 'Victim should not have any OIDC link');
    }

    private function logOut(): void
    {
        $container = $this->client->getContainer();

        // Clear token storage
        $container->get(TokenStorageInterface::class)->setToken(null);

        // Clear cookies to ensure fresh session
        $this->client->getCookieJar()->clear();
    }
}
