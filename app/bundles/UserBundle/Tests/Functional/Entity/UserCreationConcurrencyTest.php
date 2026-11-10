<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Entity;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
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
     * @param TransactionIsolationLevel::* $isolation
     */
    #[DataProvider('isolationLevels')]
    public function testCreationLockIsHeldUntilOutermostCommit(int $isolation): void
    {
        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRegistry = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $repository = $this->em->getRepository(User::class);
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $first = $this->newUser($role, 'shared@example.com', 'first@example.com');
        $second = $this->newUser($otherEm->getReference(Role::class, $role->getId()), 'second-user', 'shared@example.com');
        $originalIsolation = $this->connection->getTransactionIsolation();

        try {
            $this->connection->setTransactionIsolation($isolation);
            $otherConnection->setTransactionIsolation($isolation);
            $otherConnection->executeStatement('SET SESSION innodb_lock_wait_timeout = 1');
            $this->connection->beginTransaction();
            $repository->saveNewUser($first);

            try {
                $otherRepository->saveNewUser($second);
                $this->fail('A conflicting registration must not commit before the first transaction finishes.');
            } catch (OidcException $exception) {
                $this->assertInstanceOf(RetryableException::class, $exception->getPrevious());
                $this->assertFalse($otherEm->contains($second));
            }

            $this->connection->commit();
            $this->assertSame(0, $repository->count(['username' => 'second-user']));
            $this->assertSame(1, $repository->count(['username' => 'shared@example.com']));
            $this->assertSame(0, $repository->count(['email' => 'shared@example.com']));
        } finally {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            $this->connection->setTransactionIsolation($originalIsolation);
            $otherEm->close();
            $otherConnection->close();
        }
    }

    public function testCollisionInsertedBeforeFlushRollsBackRegistration(): void
    {
        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRegistry = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $repository = $this->em->getRepository(User::class);
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $first = $this->newUser($role, 'shared@example.com', 'first@example.com');
        $second = $this->newUser($otherEm->getReference(Role::class, $role->getId()), 'second-user', 'shared@example.com');
        $originalIsolation = $this->connection->getTransactionIsolation();
        $listener = new class($first, $second, $otherRepository) {
            public function __construct(private readonly User $first, private readonly User $second, private readonly UserRepository $repository)
            {
            }

            public function prePersist(PrePersistEventArgs $event): void
            {
                if ($event->getObject() === $this->first) {
                    // Commit a competing writer after availability checks but before the first INSERT.
                    $this->repository->saveEntity($this->second);
                }
            }
        };

        try {
            $this->connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
            $otherConnection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
            $this->em->getEventManager()->addEventListener([Events::prePersist], $listener);
            $exception = $this->assertIdentityRejected($repository, $first);
            $this->assertNotInstanceOf(\Throwable::class, $exception->getPrevious(), 'The conflict must be detected by the identity check, not a failed competing INSERT.');
            $this->assertFalse($this->em->contains($first));
            $this->assertSame(0, $repository->count(['username' => 'shared@example.com']));
            $this->assertSame(1, $repository->count(['email' => 'shared@example.com']));
        } finally {
            $this->em->getEventManager()->removeEventListener([Events::prePersist], $listener);
            $this->connection->setTransactionIsolation($originalIsolation);
            $otherEm->close();
            $otherConnection->close();
        }
    }

    /**
     * @return iterable<string, array{TransactionIsolationLevel::*}>
     */
    public static function isolationLevels(): iterable
    {
        yield 'read committed' => [TransactionIsolationLevel::READ_COMMITTED];
        yield 'repeatable read' => [TransactionIsolationLevel::REPEATABLE_READ];
    }

    #[DataProvider('collidingIdentities')]
    public function testRecheckSeesUsersCommittedAfterTransactionSnapshot(string $username, string $email): void
    {
        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRegistry = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $repository = $this->em->getRepository(User::class);
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $candidate = $this->newUser($otherEm->getReference(Role::class, $role->getId()), $username, $email);

        try {
            $otherConnection->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
            $otherConnection->beginTransaction();
            $table = $otherConnection->quoteIdentifier($otherEm->getClassMetadata(User::class)->getTableName());
            $initialCount = (int) $otherConnection->fetchOne("SELECT COUNT(*) FROM $table");

            $repository->saveNewUser($this->newUser($role, 'first-login@example.com', 'first@example.com'));
            $this->assertSame($initialCount, (int) $otherConnection->fetchOne("SELECT COUNT(*) FROM $table"));
            $this->assertIdentityRejected($otherRepository, $candidate);
            $this->assertSame($initialCount + 1, $repository->count([]));
        } finally {
            if ($otherConnection->isTransactionActive()) {
                $otherConnection->rollBack();
            }
            $otherEm->close();
            $otherConnection->close();
        }
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

    private function assertIdentityRejected(UserRepository $repository, User $user): OidcException
    {
        try {
            $repository->saveNewUser($user);
            $this->fail('The stale candidate must not be inserted.');
        } catch (OidcException $exception) {
            $this->assertSame('mautic.open_id.registration.exception.identity_taken', $exception->getMessage());
            if ($user->getId()) {
                $this->assertNotInstanceOf(User::class, $this->em->getRepository(User::class)->find($user->getId()));
            }

            return $exception;
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
