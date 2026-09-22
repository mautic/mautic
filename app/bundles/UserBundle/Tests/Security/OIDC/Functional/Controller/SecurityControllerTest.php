<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Tests\Security\OIDC\Functional\WebLoginTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class SecurityControllerTest extends MauticMysqlTestCase
{
    use WebLoginTrait;

    private const REQUIRED_LOGIN_PATH = '/s/open_id/required';
    private const LOGIN_CHECK_PATH    = '/s/open_id/login_check';
    private const LOGIN_PATH          = '/s/open_id/login';
    private const MAUTIC_LOGIN_PATH   = '/s/login';
    private const DASHBOARD_PATH      = '/s/dashboard';

    protected function setUp(): void
    {
        parent::setUp();

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

        $subjectId = new OidcSubjectId();
        $subjectId->setUser($linkedUser);
        $subjectId->setSubjectID('linked_admin');
        $this->em->persist($subjectId);

        $this->em->flush();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
    public function testRequireLoginActionWhenNotLoggedIn(ParametersBuilder $parametersBuilder): void
    {
        $parameters = $parametersBuilder->build();
        $crawler    = $this->makeRequest($parameters, self::REQUIRED_LOGIN_PATH);

        self::assertResponseIsSuccessful();
        $this->assertStringEndsWith(self::MAUTIC_LOGIN_PATH, $crawler->getUri());
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
        $parameters = $parametersBuilder->build();
        $this->makeRequest($parameters, self::LOGIN_CHECK_PATH);

        self::assertResponseStatusCodeSame(404);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enabledParametersProvider')]
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
        $parameters    = $parametersBuilder->build();
        $openIdClient  = $this->createMock(ClientInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(new RedirectResponse('https://mautic.com'));
        $this->client->getContainer()->set(ClientInterface::class, $openIdClient);
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

        $openIdClient = $this->createMock(ClientInterface::class);
        $openIdClient->expects($this->once())
            ->method('authenticate')
            ->willReturn(new RedirectResponse('https://mautic.com'));
        $this->client->getContainer()->set(ClientInterface::class, $openIdClient);
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
        $openIdClient  = $this->createMock(ClientInterface::class);
        $this->client->getContainer()->set(ClientInterface::class, $openIdClient);

        $openIdClient->expects($this->once())
            ->method('requestUserInfo')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'unlinked_admin@mautic.local',
                'preferred_username' => 'unlinked_admin',
                'given_name'         => 'unlinked_admin',
                'family_name'        => 'unlinked_admin',
            ]);
        $openIdClient->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'unlinked_admin@mautic.local',
                'preferred_username' => 'unlinked_admin',
                'given_name'         => 'unlinked_admin',
                'family_name'        => 'unlinked_admin',
            ]);
        $openIdClient->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

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
        $openIdClient  = $this->createMock(ClientInterface::class);
        $this->client->getContainer()->set(ClientInterface::class, $openIdClient);

        $openIdClient->expects($this->once())
            ->method('requestUserInfo')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'new_admin@mautic.local',
                'preferred_username' => 'new_admin',
                'given_name'         => 'new_admin',
                'family_name'        => 'new_admin',
            ]);
        $openIdClient->expects($this->once())
            ->method('getVerifiedClaims')
            ->willReturn([
                'sub'                => '123',
                'email'              => 'new_admin@mautic.local',
                'preferred_username' => 'new_admin',
                'given_name'         => 'new_admin',
                'family_name'        => 'new_admin',
            ]);
        $openIdClient->expects($this->atLeastOnce())
            ->method('getMappingField')
            ->willReturn('sub');

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
        $this->client->disableReboot();

        return $this->client->request('GET', $path, $requestParameters);
    }
}
