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

/**
 * Cross-DB concurrency coverage for UserRepository::saveNewUser().
 *
 * Diverges from upstream only where PostgreSQL requires it:
 * - DatabasePlatform::setLockWaitTimeout() instead of raw innodb_lock_wait_timeout
 * - Accept any DBAL previous exception on lock wait (PG SQLSTATE 55P03 is not always
 *   LockWaitTimeoutException)
 * - Do not assert that the secondary EntityManager is closed (upstream relied on
 *   wrapInTransaction() closing the EM; saveNewUser no longer does that)
 */
final class UserCreationConcurrencyTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testConcurrentRegistrationWaitsAndThenRejectsTheDuplicate(): void
    {
        $repository = $this->em->getRepository(User::class);
        $role       = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $first = $this->newUser($role, 'shared@example.com', 'first@example.com');

        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm         = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRegistry   = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $second          = $this->newUser(
            $otherEm->getReference(Role::class, $role->getId()),
            'second-user',
            'shared@example.com'
        );

        try {
            DatabasePlatform::setLockWaitTimeout($otherConnection, 1);
            $this->connection->beginTransaction();
            // Holds MySQL gap locks / PostgreSQL advisory locks until outer commit.
            $repository->saveNewUser($first);

            try {
                $otherRepository->saveNewUser($second);
                $this->fail('The second registration must wait for the first transaction.');
            } catch (OidcException $exception) {
                $this->assertNull($second->getId());
                $previous = $exception->getPrevious();
                $this->assertInstanceOf(\Throwable::class, $previous, 'Expected a lock-timeout or constraint previous exception');
                // MySQL: LockWaitTimeoutException; PostgreSQL: often plain DriverException (55P03).
                $this->assertTrue(
                    $previous instanceof LockWaitTimeoutException
                    || $previous instanceof DBALException,
                    'Previous exception must be a DBAL lock/driver exception, got: '.$previous::class
                );
            }

            $this->connection->commit();

            $this->assertIdentityRejected($repository, $this->newUser($role, 'second-user', 'shared@example.com'));
            $this->assertSame(1, $repository->count(['username' => 'shared@example.com']));
            $this->assertSame(0, $repository->count(['email' => 'shared@example.com']));
        } finally {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            try {
                DatabasePlatform::setLockWaitTimeout($otherConnection, 0);
            } catch (\Throwable) {
                // connection may be unusable after a lock timeout
            }
            $otherEm->close();
            $otherConnection->close();
        }
    }

    #[DataProvider('collidingIdentities')]
    public function testRejectsExistingIdentityBeforeInserting(string $username, string $email): void
    {
        $repository = $this->em->getRepository(User::class);
        $role       = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $repository->saveNewUser($this->newUser($role, 'first-login@example.com', 'first@example.com'));
        $initialCount = $repository->count([]);

        $this->assertIdentityRejected($repository, $this->newUser($role, $username, $email));

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
