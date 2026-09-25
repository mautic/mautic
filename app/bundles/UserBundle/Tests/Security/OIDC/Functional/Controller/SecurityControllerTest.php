<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Tests\Security\OIDC\Double\Service\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final class SecurityControllerTest extends MauticMysqlTestCase
{
    private const REQUIRED_LOGIN_PATH = '/s/open_id/required';
    private const LOGIN_CHECK_PATH    = '/s/open_id/login_check';
    private const LOGIN_PATH          = '/s/open_id/login';
    private const MAUTIC_LOGIN_PATH   = '/s/login';
    private const DASHBOARD_PATH      = '/s/dashboard';

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

        $linkedUser = new User();
        $linkedUser->setUsername('linked_admin');
        $linkedUser->setPassword('linked_admin');
        $linkedUser->setFirstName('linked_admin');
        $linkedUser->setLastName('linked_admin');
        $linkedUser->setEmail('linked_admin@mautic.local');
        $linkedUser->setRole($role);
        $this->em->persist($linkedUser);

        $unlinkedUser = new User();
        $unlinkedUser->setUsername('unlinked_admin');
        $unlinkedUser->setPassword('unlinked_admin');
        $unlinkedUser->setFirstName('unlinked_admin');
        $unlinkedUser->setLastName('unlinked_admin');
        $unlinkedUser->setEmail('unlinked_admin@mautic.local');
        $unlinkedUser->setRole($role);
        $this->em->persist($unlinkedUser);

        $this->em->persist(new OidcSubjectId($linkedUser, 'linked_admin'));

        $this->em->flush();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testRequireLoginActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        // When OIDC is enabled, unauthenticated users are redirected to OIDC provider
        Client::$authenticateResponse = new RedirectResponse('https://oidc-provider.example.com');

        $parameters = $parametersBuilder->build();
        $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        // Should redirect to OIDC provider (simulated)
        self::assertResponseIsSuccessful();
    }

    public function testRequireLoginActionWhenNotLoggedInAndOpenIdIsDisabled(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testRequireLoginActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        self::logInWithPassword('unlinked_admin');
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        if ($parameters->isRequired()) {
            $this->assertStringEndsWith(self::REQUIRED_LOGIN_PATH, $crawler->getUri());

            return;
        }

        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testRequireLoginActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        self::logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    public function testRequiredLoginActionWhenDisabledAndNotLoggedIn(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    public function testRequiredLoginActionWhenDisabledAndLoggedInUsingForm(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginCheckActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        // Set authenticate response to prevent redirect loop when accessing protected endpoint
        Client::$authenticateResponse = new RedirectResponse('https://oidc-provider.example.com');

        $parameters = $parametersBuilder->build();
        $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        // login_check via GET should return 404 (it's meant for form POST)
        self::assertResponseStatusCodeSame(404);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginCheckActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        if ($parameters->isRequired()) {
            // When OIDC is required but user is logged in with password (not OIDC),
            // login_check returns 404 (it's meant to be handled by the authenticator)
            self::assertResponseStatusCodeSame(404);

            return;
        }

        // When OIDC is optional, authenticated users should be redirected to dashboard
        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginCheckActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    public function testLoginCheckActionWhenDisabledAndNotLoggedIn(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    public function testLoginCheckActionWhenDisabledAndLoggedInUsingForm(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        // Configure the test double to return a redirect
        Client::$authenticateResponse = new RedirectResponse('https://mautic.com');

        $parameters = $parametersBuilder->build();
        $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithPassword('unlinked_admin');

        if (!$parameters->isRequired()) {
            $crawler = $this->makeRequest($parameters, self::LOGIN_PATH);
            self::assertResponseIsSuccessful();
            $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());

            return;
        }

        // Configure the test double to return a redirect
        Client::$authenticateResponse = new RedirectResponse('https://mautic.com');

        $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginActionLinksUser(ParametersBuilder $parametersBuilder): void
    {
        $userRepo      = $this->em->getRepository(User::class);
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        // Configure the test double with user data
        Client::$userInfoResponse = [
            'sub'                => '123',
            'email'              => 'unlinked_admin@mautic.local',
            'preferred_username' => 'unlinked_admin',
            'given_name'         => 'unlinked_admin',
            'family_name'        => 'unlinked_admin',
        ];
        Client::$verifiedClaimsResponse = [
            'sub'                => '123',
            'email'              => 'unlinked_admin@mautic.local',
            'preferred_username' => 'unlinked_admin',
            'given_name'         => 'unlinked_admin',
            'family_name'        => 'unlinked_admin',
        ];
        Client::$mappingFieldResponse = 'sub';

        $parameters = $parametersBuilder->build();
        $this->logInWithPassword('unlinked_admin');
        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH, [
            'code'  => 'code',
            'state' => 'state',
        ]);

        $user      = $userRepo->findOneBy(['email' => 'unlinked_admin@mautic.local']);
        $this->assertInstanceOf(User::class, $user);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $user, 'subjectID' => '123']);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
        $this->assertInstanceOf(OidcSubjectId::class, $subjectId);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testLoginActionCreatesUser(ParametersBuilder $parametersBuilder): void
    {
        $userRepo      = $this->em->getRepository(User::class);
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);

        // Configure the test double with new user data
        Client::$userInfoResponse = [
            'sub'                => '123',
            'email'              => 'new_admin@mautic.local',
            'preferred_username' => 'new_admin',
            'given_name'         => 'new_admin',
            'family_name'        => 'new_admin',
        ];
        Client::$verifiedClaimsResponse = [
            'sub'                => '123',
            'email'              => 'new_admin@mautic.local',
            'preferred_username' => 'new_admin',
            'given_name'         => 'new_admin',
            'family_name'        => 'new_admin',
        ];
        Client::$mappingFieldResponse = 'sub';

        $roleRepo = $this->em->getRepository(Role::class);
        $this->assertInstanceOf(RoleRepository::class, $roleRepo);

        $adminRole = $roleRepo->findOneBy(['name' => 'Admin']);
        $this->assertInstanceOf(Role::class, $adminRole);

        $parameters = $parametersBuilder->withRegisteredUserRoleId($adminRole->getId())->build();

        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH, [
            'code'  => 'code',
            'state' => 'state',
        ]);

        if (!$parameters->isUserRegistrationAllowed()) {
            self::assertResponseIsSuccessful();
            $this->assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());

            return;
        }

        $user      = $userRepo->findOneBy(['email' => 'new_admin@mautic.local']);
        $this->assertInstanceOf(User::class, $user);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $user, 'subjectID' => '123']);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
        $this->assertInstanceOf(OidcSubjectId::class, $subjectId);
    }

    /**
     * @return iterable<string, array<int, ParametersBuilder>>
     */
    public static function enabledParametersProvider(): iterable
    {
        yield 'Everything Enabled' => [
            new ParametersBuilder(),
        ];

        yield 'Optional' => [
            (new ParametersBuilder())->withIsRequired(false),
        ];

        yield 'Registration Disabled' => [
            (new ParametersBuilder())->withIsUserRegistrationAllowed(false),
        ];
    }

    /**
     * @param array<string, string> $requestParameters
     */
    private function makeRequest(Settings $parameters, string $path, array $requestParameters = []): Crawler
    {
        $this->client->getContainer()->set(Settings::class, $parameters);
        $this->client->followRedirects();
        $this->client->setMaxRedirects(10);
        $this->client->disableReboot();

        return $this->client->request('GET', $path, $requestParameters);
    }

    /**
     * Log in with password (form login) on 'main' firewall.
     *
     * Note: Mautic uses a shared firewall context 'mautic' for all firewalls.
     * We pass 'mautic' as context so the session token is stored correctly.
     * But this means getFirewallName() returns 'mautic', not 'main'.
     * For OIDC tests that need to distinguish, we manually create tokens.
     */
    private function logInWithPassword(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $user      = $container->get(UserRepository::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        // Create a PostAuthenticationToken with 'main' firewall name
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());

        // Get the session and store token under the shared context 'mautic'
        $session = $container->get(SessionFactoryInterface::class)->createSession();
        $session->set('_security_mautic', serialize($token));
        $session->save();

        // Set session cookie so it persists across requests
        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        // Also set token storage for immediate use
        $container->get(TokenStorageInterface::class)->setToken($token);
    }

    /**
     * Log in with OpenID on 'open_id' firewall.
     *
     * Note: Mautic uses a shared firewall context 'mautic' for all firewalls.
     * We manually create a PostAuthenticationToken with 'open_id' as firewall name
     * and store it under _security_mautic so it persists across requests.
     */
    private function logInWithOpenID(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $user      = $container->get(UserRepository::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        // Create a PostAuthenticationToken with 'open_id' firewall name
        $token = new PostAuthenticationToken($user, 'open_id', $user->getRoles());

        // Get the session and store token under the shared context 'mautic'
        $session = $container->get(SessionFactoryInterface::class)->createSession();
        $session->set('_security_mautic', serialize($token));
        $session->save();

        // Set session cookie so it persists across requests
        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        // Also set token storage for immediate use
        $container->get(TokenStorageInterface::class)->setToken($token);
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
