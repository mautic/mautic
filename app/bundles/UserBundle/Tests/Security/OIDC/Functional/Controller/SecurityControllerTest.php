<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\Settings;
use Mautic\UserBundle\Security\OIDC\Service\ClientInterface;
use Mautic\UserBundle\Security\OIDC\Tests\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Security\OIDC\Tests\Functional\LoadFixturesTrait;
use Mautic\UserBundle\Security\OIDC\Tests\Functional\WebLoginTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class SecurityControllerTest extends MauticMysqlTestCase
{
    use WebLoginTrait;
    use LoadFixturesTrait {
        setUp as setUpFixtures;
    }

    private const REQUIRED_LOGIN_PATH = '/s/open_id/required';
    private const LOGIN_CHECK_PATH    = '/s/open_id/login_check';
    private const LOGIN_PATH          = '/s/open_id/login';
    private const MAUTIC_LOGIN_PATH   = '/s/login';
    private const DASHBOARD_PATH      = '/s/dashboard';

    protected function setUp(): void
    {
        self::setUpFixtures();
        $this->client = static::createClient();
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testRequireLoginActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    public function testRequireLoginActionWhenNotLoggedInAndOpenIdIsDisabled(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testRequireLoginActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        self::logInWithPassword('unlinked_admin');
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        if ($parameters->isRequired()) {
            self::assertStringEndsWith(self::REQUIRED_LOGIN_PATH, $crawler->getUri());

            return;
        }

        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testRequireLoginActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        self::logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    public function testRequiredLoginActionWhenDisabledAndNotLoggedIn(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    public function testRequiredLoginActionWhenDisabledAndLoggedInUsingForm(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginCheckActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginCheckActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        if ($parameters->isRequired()) {
            self::assertResponseStatusCodeSame(404);

            return;
        }

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginCheckActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    public function testLoginCheckActionWhenDisabledAndNotLoggedIn(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $crawler    = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
    }

    public function testLoginCheckActionWhenDisabledAndLoggedInUsingForm(): void
    {
        $parameters = (new ParametersBuilder())->withIsEnabled(false)->build();
        $this->logInWithPassword();
        $crawler = $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        $parameters    = $parametersBuilder->build();
        $openIdClient  = self::createMock(ClientInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(new RedirectResponse('https://mautic.com'));
        $this->client->getContainer()->set('mautic.open_id.client', $openIdClient);
        $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginActionWhenLoggedInUsingForm(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithPassword('unlinked_admin');

        if (!$parameters->isRequired()) {
            $crawler = $this->makeRequest($parameters, self::LOGIN_PATH);
            self::assertResponseIsSuccessful();
            self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());

            return;
        }

        $openIdClient = self::createMock(ClientInterface::class);
        $openIdClient->expects(self::once())
            ->method('authenticate')
            ->willReturn(new RedirectResponse('https://mautic.com'));
        $this->client->getContainer()->set('mautic.open_id.client', $openIdClient);
        $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginActionWhenLoggedInUsingOpenId(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $this->logInWithOpenID();
        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginActionLinksUser(ParametersBuilder $parametersBuilder): void
    {
        $userRepo      = $this->em->getRepository(User::class);
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $openIdClient  = self::createMock(ClientInterface::class);
        $this->client->getContainer()->set('mautic.open_id.client', $openIdClient);

        $openIdClient->expects(self::once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'unlinked_admin@mautic.local',
                'preferred_username' => 'unlinked_admin',
                'given_name'         => 'unlinked_admin',
                'family_name'        => 'unlinked_admin',
            ]);
        $openIdClient->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $parameters = $parametersBuilder->build();
        $this->logInWithPassword('unlinked_admin');
        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH, [
            'code'  => 'code',
            'state' => 'state',
        ]);

        $user      = $userRepo->findOneBy(['email' => 'unlinked_admin@mautic.local']);
        \assert($user instanceof User);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $user, 'subjectID' => '123']);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
        self::assertNotNull($subjectId);
    }

    /**
     * @dataProvider enabledParametersProvider
     */
    public function testLoginActionCreatesUser(ParametersBuilder $parametersBuilder): void
    {
        $userRepo      = $this->em->getRepository(User::class);
        $subjectIdRepo = $this->em->getRepository(OidcSubjectId::class);
        $openIdClient  = self::createMock(ClientInterface::class);
        $this->client->getContainer()->set('mautic.open_id.client', $openIdClient);

        $openIdClient->expects(self::once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'new_admin@mautic.local',
                'preferred_username' => 'new_admin',
                'given_name'         => 'new_admin',
                'family_name'        => 'new_admin',
            ]);
        $openIdClient->expects(self::atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

        $roleRepo = $this->em->getRepository(Role::class);
        \assert($roleRepo instanceof RoleRepository);

        $adminRole = $roleRepo->findOneBy(['name' => 'Admin']);
        \assert($adminRole instanceof Role);

        $parameters = $parametersBuilder->withRegisteredUserRole($adminRole)->build();

        $crawler = $this->makeRequest($parameters, self::LOGIN_PATH, [
            'code'  => 'code',
            'state' => 'state',
        ]);

        if (!$parameters->isUserRegistrationAllowed()) {
            self::assertResponseIsSuccessful();
            self::assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());

            return;
        }

        $user      = $userRepo->findOneBy(['email' => 'new_admin@mautic.local']);
        \assert($user instanceof User);
        $subjectId = $subjectIdRepo->findOneBy(['user' => $user, 'subjectID' => '123']);

        self::assertResponseIsSuccessful();
        self::assertStringEndsWith(self::DASHBOARD_PATH, $crawler->getUri());
        self::assertNotNull($subjectId);
    }

    /**
     * @return iterable<string, array<int, ParametersBuilder>>
     */
    public function enabledParametersProvider(): iterable
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
        $this->client->getContainer()->set('mautic.open_id.settings', $parameters);
        $this->client->followRedirects();
        $this->client->disableReboot();

        return $this->client->request('GET', $path, $requestParameters);
    }
}
