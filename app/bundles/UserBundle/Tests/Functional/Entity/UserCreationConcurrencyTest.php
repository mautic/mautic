<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Entity;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\ORM\EntityManager;
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

    public function testConcurrentRegistrationWaitsAndThenRejectsTheDuplicate(): void
    {
        $repository = $this->em->getRepository(User::class);
        $role = $this->em->getRepository(Role::class)->findOneBy([]);
        $this->assertInstanceOf(Role::class, $role);
        $first = $this->newUser($role, 'shared@example.com', 'first@example.com');

        $otherConnection = DriverManager::getConnection($this->connection->getParams());
        $otherEm = new EntityManager($otherConnection, $this->em->getConfiguration(), $this->em->getEventManager());
        $otherRegistry = $this->createStub(ManagerRegistry::class);
        $otherRegistry->method('getManagerForClass')->willReturn($otherEm);
        $otherRepository = new UserRepository($otherRegistry);
        $second = $this->newUser($otherEm->getReference(Role::class, $role->getId()), 'second-user', 'shared@example.com');

        try {
            $otherConnection->executeStatement('SET SESSION innodb_lock_wait_timeout = 1');
            $this->connection->beginTransaction();
            $repository->saveNewUser($first);

            // Another registration must wait until the first one commits.
            try {
                $otherRepository->saveNewUser($second);
                $this->fail('The second registration must wait for the first transaction.');
            } catch (OidcException $exception) {
                $this->assertInstanceOf(LockWaitTimeoutException::class, $exception->getPrevious());
                $this->assertNull($second->getId());
            }

            $this->connection->commit();

            // Once the first user is committed, the same candidate is rejected before insertion.
            $this->assertIdentityRejected($otherRepository, $second);
            $this->assertSame(1, $repository->count(['username' => 'shared@example.com']));
            $this->assertSame(0, $repository->count(['email' => 'shared@example.com']));
        } finally {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            $otherEm->close();
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
