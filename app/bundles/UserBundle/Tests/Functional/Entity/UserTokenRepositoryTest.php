<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Entity\UserTokenRepository;

final class UserTokenRepositoryTest extends MauticMysqlTestCase
{
    private UserTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->em->getRepository(UserToken::class);

        // Guarantee a deterministic starting point regardless of fixtures.
        $table = $this->em->getClassMetadata(UserToken::class)->getTableName();
        $this->em->getConnection()->executeStatement("DELETE FROM {$table}");
    }

    public function testDeleteExpiredDryRunReturnsRealCount(): void
    {
        $user = $this->em->find(User::class, 1);
        $this->assertInstanceOf(User::class, $user);

        // Three expired tokens and one that is still valid.
        $this->createToken($user, new \DateTime('-2 days'));
        $this->createToken($user, new \DateTime('-1 day'));
        $this->createToken($user, new \DateTime('-1 hour'));
        $this->createToken($user, new \DateTime('+1 day'));
        $this->em->flush();
        $this->em->clear();

        // The dry run must report the real number of expired tokens, not a constant 1.
        $this->assertSame(3, $this->repository->deleteExpired(true));
        // The dry run must not delete anything.
        $this->assertSame(4, (int) $this->repository->count([]));
        // The real run deletes exactly the expired tokens and returns the same count.
        $this->assertSame(3, $this->repository->deleteExpired(false));
        $this->assertSame(1, (int) $this->repository->count([]));
    }

    public function testDeleteExpiredDryRunReturnsZeroWhenNothingExpired(): void
    {
        $user = $this->em->find(User::class, 1);
        $this->assertInstanceOf(User::class, $user);

        $this->createToken($user, new \DateTime('+1 day'));
        $this->em->flush();
        $this->em->clear();

        $this->assertSame(0, $this->repository->deleteExpired(true));
    }

    private function createToken(User $user, \DateTime $expiration): void
    {
        $token = new UserToken();
        $token->setUser($user);
        $token->setAuthorizator('reset-password');
        $token->setSecret(bin2hex(random_bytes(16)));
        $token->setExpiration($expiration);
        $token->setOneTimeOnly(true);
        $this->em->persist($token);
    }
}
