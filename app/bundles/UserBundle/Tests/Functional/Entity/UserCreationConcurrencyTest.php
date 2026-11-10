<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Entity;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Exception\OidcException;
use PHPUnit\Framework\Attributes\DataProvider;

final class UserCreationConcurrencyTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    #[DataProvider('isolationLevels')]
    public function testCreationLockIsHeldUntilOutermostCommit(int $isolation): void
    {
        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRepository = new UserRepository($otherEm, $otherEm->getClassMetadata(User::class));
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
                $this->fail('A concurrent connection must not insert before the first transaction commits.');
            } catch (LockWaitTimeoutException) {
                $this->assertNull($second->getId());
            }

            $this->connection->commit();
            $this->assertIdentityRejected($otherRepository, $second);
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

    /**
     * @return iterable<string, array{int}>
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
        $otherRepository = new UserRepository($otherEm, $otherEm->getClassMetadata(User::class));
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

    private function assertIdentityRejected(UserRepository $repository, User $user): void
    {
        try {
            $repository->saveNewUser($user);
            $this->fail('The stale candidate must not be inserted.');
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
