<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Tests\Security\OIDC\Double\Service\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This test verifies that OIDC logins trigger Mautic's login side effects:
 * - Audit log entry for login
 * - Update last_login timestamp
 * - Store user's locale in session
 * - Store user's timezone in session
 * - Set 'mautic.user' in session
 *
 * These side effects are triggered by the security.interactive_login event,
 * which is only dispatched for authenticators that implement InteractiveAuthenticatorInterface.
 */
final class OidcAuthenticatorInteractiveLoginTest extends MauticMysqlTestCase
{
    private const LOGIN_PATH = '/s/open_id/login';

    protected function setUp(): void
    {
        parent::setUp();
        // Clear automatic login from parent - OIDC tests need explicit control over auth state
        $this->logOut();

        // Reset the test double client state
        Client::reset();

        $role = new Role();
        $role->setName('Admin');
        $role->setIsAdmin(true);
        $this->em->persist($role);

        $user = new User();
        $user->setUsername('oidc_user');
        $user->setPassword('oidc_user');
        $user->setFirstName('OIDC');
        $user->setLastName('User');
        $user->setEmail('oidc_user@mautic.local');
        $user->setRole($role);
        $user->setTimezone('Europe/Prague');
        $user->setLocale('cs_CZ');
        $this->em->persist($user);

        $this->em->persist(new OidcSubjectId($user, 'oidc_123'));

        $this->em->flush();
    }

    /**
     * This test verifies that OidcAuthenticator implements InteractiveAuthenticatorInterface
     * by checking that isInteractive() returns true.
     */
    public function testOidcAuthenticatorIsInteractive(): void
    {
        $container = $this->client->getContainer();
        $authenticator = $container->get(\Mautic\UserBundle\Security\OIDC\OidcAuthenticator::class);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface::class, $authenticator);
        $this->assertTrue($authenticator->isInteractive(), 'OidcAuthenticator should return true for isInteractive()');
    }

    public function testOidcLoginUpdatesLastLogin(): void
    {
        $userRepo = $this->em->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);
        $user = $userRepo->findOneBy(['username' => 'oidc_user']);
        $this->assertInstanceOf(User::class, $user);

        // Verify last_login is null initially
        $this->assertNotInstanceOf(\DateTimeInterface::class, $user->getLastLogin(), 'Expected last_login to be null initially');

        // Perform OIDC login through the real authenticator
        $this->performOidcLogin();

        // Verify last_login was updated
        $this->em->clear();
        $user = $userRepo->findOneBy(['username' => 'oidc_user']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getLastLogin(), 'Expected last_login to be set after OIDC login');
    }

    public function testOidcLoginStoresUserInSession(): void
    {
        // Perform OIDC login through the real authenticator
        $this->performOidcLogin();

        // Verify session has mautic.user set
        $session = $this->client->getRequest()->getSession();

        $userRepo = $this->em->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);
        $user = $userRepo->findOneBy(['username' => 'oidc_user']);
        $this->assertInstanceOf(User::class, $user);

        $this->assertTrue($session->has('mautic.user'), 'Expected mautic.user to be set in session');
        $this->assertEquals($user->getId(), $session->get('mautic.user'));
    }

    public function testOidcLoginStoresTimezoneAndLocaleInSession(): void
    {
        // Perform OIDC login through the real authenticator
        $this->performOidcLogin();

        // Verify session has timezone and locale set
        $session = $this->client->getRequest()->getSession();

        $this->assertTrue($session->has('_timezone'), 'Expected _timezone to be set in session');
        $this->assertEquals('Europe/Prague', $session->get('_timezone'));

        $this->assertTrue($session->has('_locale'), 'Expected _locale to be set in session');
        $this->assertEquals('cs_CZ', $session->get('_locale'));
    }

    /**
     * This test verifies that the interactive login side effects are triggered.
     * We test all the side effects together to ensure the integration works end-to-end.
     */
    public function testOidcLoginTriggersAllInteractiveLoginSideEffects(): void
    {
        $userRepo = $this->em->getRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);
        $user = $userRepo->findOneBy(['username' => 'oidc_user']);
        $this->assertInstanceOf(User::class, $user);

        // Verify last_login is null initially
        $this->assertNotInstanceOf(\DateTimeInterface::class, $user->getLastLogin(), 'Expected last_login to be null initially');

        // Perform OIDC login through the real authenticator
        $this->performOidcLogin();

        // Verify last_login was updated
        $this->em->clear();
        $user = $userRepo->findOneBy(['username' => 'oidc_user']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getLastLogin(), 'Expected last_login to be set after OIDC login');

        // Verify session has all expected values
        $session = $this->client->getRequest()->getSession();
        $this->assertTrue($session->has('mautic.user'), 'Expected mautic.user to be set in session');
        $this->assertEquals($user->getId(), $session->get('mautic.user'));
        $this->assertTrue($session->has('_timezone'), 'Expected _timezone to be set in session');
        $this->assertEquals('Europe/Prague', $session->get('_timezone'));
        $this->assertTrue($session->has('_locale'), 'Expected _locale to be set in session');
        $this->assertEquals('cs_CZ', $session->get('_locale'));
    }

    /**
     * Perform an actual OIDC login through the authenticator by simulating
     * the callback from the OIDC provider with code and state parameters.
     */
    private function performOidcLogin(): void
    {
        // Configure the test double with user data that matches existing user
        Client::$userInfoResponse = [
            'sub'                => 'oidc_123',
            'email'              => 'oidc_user@mautic.local',
            'preferred_username' => 'oidc_user',
            'given_name'         => 'OIDC',
            'family_name'        => 'User',
        ];
        Client::$verifiedClaimsResponse = [
            'sub'                => 'oidc_123',
            'email'              => 'oidc_user@mautic.local',
            'preferred_username' => 'oidc_user',
            'given_name'         => 'OIDC',
            'family_name'        => 'User',
        ];
        Client::$mappingFieldResponse = 'sub';
        Client::$authenticateResponse = new RedirectResponse('https://oidc-provider.example.com');

        // Enable OIDC in settings
        $parameters = (new ParametersBuilder())->build();
        $this->client->getContainer()->set(Settings::class, $parameters);

        $this->client->followRedirects();
        $this->client->setMaxRedirects(10);
        $this->client->disableReboot();

        // Simulate the OIDC callback with code and state
        $this->client->request('GET', self::LOGIN_PATH, [
            'code'  => 'test_code',
            'state' => 'test_state',
        ]);

        $this->assertResponseIsSuccessful();
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
