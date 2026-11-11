<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Entity;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Exception\OidcException;
use PHPUnit\Framework\Attributes\DataProvider;

final class UserCreationConcurrencyTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * If a prior assertion left the EM closed, reboot before tearDown's resetDatabase()
     * tries to reload fixtures (PostgreSQL path always reloads essential fixtures after TRUNCATE).
     */
    protected function beforeTearDown(): void
    {
        if (isset($this->em) && !$this->em->isOpen()) {
            $this->setUpSymfony($this->configParams);
        }

        parent::beforeTearDown();
    }

    public function testConcurrentRegistrationWaitsAndThenRejectsTheDuplicate(): void
    {
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);

        $platform    = $this->connection->getDatabasePlatform();
        $identifiers = [
            DatabasePlatform::normalizeSearchValue($platform, 'shared@example.com'),
            DatabasePlatform::normalizeSearchValue($platform, 'first@example.com'),
        ];

        // Independent connection/EM — do not share EventManager with the primary EM.
        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm         = new EntityManager($otherConnection, clone $this->em->getConfiguration());
        $otherRegistry   = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $second          = $this->newUser(
            $otherEm->getReference(Role::class, $role->getId()),
            'second-user',
            'shared@example.com'
        );

        try {
            // Hold identity locks + first user row open on the PRIMARY connection.
            // Do not call saveNewUser() here: it would nest transactions and complicate rollback.
            $this->connection->beginTransaction();

            DatabasePlatform::acquireIdentityLocks($this->connection, $identifiers);

            $first = $this->newUser($role, 'shared@example.com', 'first@example.com');
            $this->em->persist($first);
            $this->em->flush();

            // Also take the same row locks MySQL gap-locking would hold after saveNewUser().
            $usernameMatch = DatabasePlatform::getCaseInsensitiveComparison($platform, 'u.username', ':identifiers', 'IN');
            $emailMatch    = DatabasePlatform::getCaseInsensitiveComparison($platform, 'u.email', ':identifiers', 'IN');
            $this->em->createQueryBuilder()
                ->select('1')
                ->from(User::class, 'u')
                ->where("{$usernameMatch} OR {$emailMatch}")
                ->setParameter('identifiers', $identifiers)
                ->setMaxResults(1)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            // Session-level timeout must be set before the other session opens its transaction.
            DatabasePlatform::setLockWaitTimeout($otherConnection, 1);

            try {
                $otherRepository->saveNewUser($second);
                $this->fail('The second registration must wait for the first transaction.');
            } catch (OidcException $exception) {
                $this->assertSame('mautic.open_id.registration.exception.identity_taken', $exception->getMessage());
                $this->assertNull($second->getId());
                $previous = $exception->getPrevious();
                $this->assertNotNull($previous, 'Expected a lock-timeout previous exception');
                $this->assertTrue(
                    $previous instanceof LockWaitTimeoutException
                    || $previous instanceof DBALException,
                    'Previous exception must be a DBAL lock/driver exception, got: '.$previous::class
                );
            }

            $this->connection->commit();

            $this->assertTrue($this->em->isOpen(), 'Primary EntityManager must stay open');

            $repository = $this->em->getRepository(User::class);
            $this->assertIdentityRejected($repository, $this->newUser($role, 'second-user', 'shared@example.com'));
            $this->assertSame(1, $repository->count(['username' => 'shared@example.com']));
            $this->assertSame(0, $repository->count(['email' => 'shared@example.com']));
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        } finally {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            try {
                DatabasePlatform::setLockWaitTimeout($otherConnection, 0);
            } catch (\Throwable) {
                // ignore — connection may be broken after lock timeout
            }

            if ($otherEm->isOpen()) {
                $otherEm->close();
            }
            $otherConnection->close();
        }
    }

    #[DataProvider('collidingIdentities')]
    public function testRejectsExistingIdentityBeforeInserting(string $username, string $email): void
    {
        $repository = $this->em->getRepository(User::class);
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $repository->saveNewUser($this->newUser($role, 'first-login@example.com', 'first@example.com'));
        $initialCount = $repository->count([]);

        $this->assertIdentityRejected($repository, $this->newUser($role, $username, $email));

        $this->assertTrue($this->em->isOpen(), 'Primary EntityManager must stay open after rejection');
        $this->assertSame($initialCount, $repository->count([]));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function collidingIdentities(): iterable
    {
        yield 'username matches email' => ['first@example.com', 'second@example.com'];
        yield 'email matches username' => ['second-user', 'first-login@example.com'];
        yield 'case-insensitive cross-column collision' => ['FIRST@example.com', 'second@example.com'];
        yield 'duplicate username' => ['first-login@example.com', 'second@example.com'];
        yield 'duplicate email' => ['second-user', 'first@example.com'];
    }

    private function assertIdentityRejected(UserRepository $repository, User $user): void
    {
        try {
            $repository->saveNewUser($user);
            $this->fail('The duplicate user must not be inserted.');
        } catch (OidcException $exception) {
            $this->assertSame('mautic.open_id.registration.exception.identity_taken', $exception->getMessage());
            $this->assertNull($user->getId());
        }
    }

    private function newUser(Role $role, string $username, string $email): User
    {
        $user = new User();
        $user->setRole($role);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword('test-password');
        $user->setFirstName('New');
        $user->setLastName('User');

        return $user;
    }
}
