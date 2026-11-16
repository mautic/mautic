<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Tests\Security\OIDC\Double\Service\Client;
use Mautic\UserBundle\UserEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OidcRegistrationSecurityTest extends MauticMysqlTestCase
{
    private int $victimId;
    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        Client::reset();
        $this->client->getContainer()->get(TokenStorageInterface::class)->setToken(null);
        $this->client->getCookieJar()->clear();
        $this->client->disableReboot();
        $this->client->followRedirects(false);

        $role = new Role();
        $role->setName('OIDC registrants');
        $role->setIsAdmin(false);
        $this->em->persist($role);

        $victim = new User();
        $victim->setUsername('victim-login@example.com');
        $victim->setEmail('victim@example.com');
        $victim->setPassword('unchanged-victim-password');
        $victim->setFirstName('Victim');
        $victim->setLastName('User');
        $victim->setRole($role);
        $this->em->persist($victim);
        $this->em->flush();

        $this->victimId = $victim->getId();
        $this->roleId   = $role->getId();

        $this->client->getContainer()->set(Settings::class, (new ParametersBuilder())
            ->withIsRequired(false)
            ->withIsUserRegistrationAllowed(true)
            ->withRegisteredUserRoleId($this->roleId)
            ->build());
    }

    protected function beforeTearDown(): void
    {
        Client::reset();
        parent::beforeTearDown();
    }

    #[DataProvider('availableIdentities')]
    public function testRegistrationCreatesAndAuthenticatesSeparateUser(?string $preferredUsername, string $expectedUsername): void
    {
        $userCount = $this->em->getRepository(User::class)->count([]);
        $this->client->getContainer()->get(EventDispatcherInterface::class)->addListener(UserEvents::USER_PRE_SAVE, static function (UserEvent $event): void {
            $event->getUser()->setTimezone('Europe/Prague');
        });

        $this->register('new@example.com', $preferredUsername);

        self::assertResponseRedirects('https://localhost/s/dashboard');
        $authenticatedUser = $this->client->getContainer()->get(TokenStorageInterface::class)->getToken()?->getUser();
        $this->assertInstanceOf(User::class, $authenticatedUser);

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'new@example.com']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotSame($this->victimId, $user->getId());
        $this->assertSame($user->getId(), $authenticatedUser->getId());
        $this->assertSame($expectedUsername, $user->getUsername());
        $this->assertSame('New', $user->getFirstName());
        $this->assertSame('Registrant', $user->getLastName());
        $this->assertSame($this->roleId, $user->getRole()->getId());
        $this->assertFalse($user->getRole()->isAdmin());
        $this->assertSame('Europe/Prague', $user->getTimezone());
        $this->assertNotEmpty($user->getPassword());
        $this->assertSame($userCount + 1, $this->em->getRepository(User::class)->count([]));

        $link = $this->em->getRepository(OidcSubjectId::class)->findOneBy(['subjectID' => 'new-subject']);
        $this->assertInstanceOf(OidcSubjectId::class, $link);
        $this->assertSame($user->getId(), $link->getUser()->getId());
        $this->assertInstanceOf(AuditLog::class, $this->em->getRepository(AuditLog::class)->findOneBy([
            'bundle' => 'user', 'object' => 'user', 'objectId' => $user->getId(), 'action' => 'create',
        ]));
        $this->assertVictimUnchanged();
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function availableIdentities(): iterable
    {
        yield 'unique preferred username' => ['new-user', 'new-user'];
        yield 'no preferred username' => [null, 'new@example.com'];
        yield 'preferred username matches existing email' => ['victim@example.com', 'new@example.com'];
        yield 'preferred username matches existing username' => ['victim-login@example.com', 'new@example.com'];
        yield 'case-insensitive email collision' => ['VICTIM@example.com', 'new@example.com'];
    }

    #[DataProvider('conflictingIdentities')]
    public function testRegistrationRejectsConflictingEmail(string $email, ?string $preferredUsername): void
    {
        $userCount = $this->em->getRepository(User::class)->count([]);
        $this->register($email, $preferredUsername);

        self::assertResponseRedirects('https://localhost/s/login');
        $this->assertNull($this->client->getContainer()->get(TokenStorageInterface::class)->getToken());
        $crawler = $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('Failed to register. This identity is already associated with an account.', $crawler->text());

        $this->em->clear();
        $this->assertSame($userCount, $this->em->getRepository(User::class)->count([]));
        $this->assertNotInstanceOf(OidcSubjectId::class, $this->em->getRepository(OidcSubjectId::class)->findOneBy(['subjectID' => 'new-subject']));
        $this->assertVictimUnchanged();
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function conflictingIdentities(): iterable
    {
        yield 'email matches existing email' => ['victim@example.com', 'new-user'];
        yield 'email matches existing username with unique preferred username' => ['victim-login@example.com', 'new-user'];
        yield 'email matches existing username with taken preferred username' => ['victim-login@example.com', 'victim@example.com'];
        yield 'email matches existing username without preferred username' => ['victim-login@example.com', null];
        yield 'case-insensitive username collision' => ['VICTIM-LOGIN@example.com', 'new-user'];
    }

    private function register(string $email, ?string $preferredUsername): void
    {
        Client::$userInfoResponse = [
            'sub'                => 'new-subject',
            'email'              => $email,
            'preferred_username' => $preferredUsername,
            'given_name'         => 'New',
            'family_name'        => 'Registrant',
        ];
        Client::$verifiedClaimsResponse = Client::$userInfoResponse;
        $this->client->request('GET', '/s/open_id/login', ['code' => 'test-code', 'state' => 'test-state']);
    }

    private function assertVictimUnchanged(): void
    {
        $victim = $this->em->getRepository(User::class)->find($this->victimId);
        $this->assertInstanceOf(User::class, $victim);
        $this->assertSame('victim-login@example.com', $victim->getUsername());
        $this->assertSame('victim@example.com', $victim->getEmail());
        $this->assertSame('unchanged-victim-password', $victim->getPassword());
        $this->assertSame('Victim', $victim->getFirstName());
        $this->assertSame('User', $victim->getLastName());
        $this->assertSame($this->roleId, $victim->getRole()->getId());
        $this->assertNotInstanceOf(OidcSubjectId::class, $this->em->getRepository(OidcSubjectId::class)->findOneBy(['user' => $this->victimId]));

        // The original identity must remain unambiguous for password sign-in.
        $provider = $this->client->getContainer()->get(UserProvider::class);
        $this->assertSame($this->victimId, $provider->loadUserByIdentifier('victim-login@example.com')->getId());
        $this->assertSame($this->victimId, $provider->loadUserByIdentifier('victim@example.com')->getId());
    }
}
