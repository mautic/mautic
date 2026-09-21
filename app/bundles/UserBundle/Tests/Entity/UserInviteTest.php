<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\UserInvite;
use PHPUnit\Framework\TestCase;

final class UserInviteTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $role       = new Role();
        $newRole    = new Role();
        $expiration = new \DateTimeImmutable('+1 day');
        $invite     = new UserInvite($role);

        $invite->setEmail('invitee@example.com')
            ->setTokenSelector('selector')
            ->setTokenVerifierHash('verifier-hash')
            ->setExpiration($expiration)
            ->setUsed(true)
            ->setRole($newRole);

        $this->assertNull($invite->getId());
        $this->assertSame('invitee@example.com', $invite->getEmail());
        $this->assertSame('selector', $invite->getTokenSelector());
        $this->assertSame('verifier-hash', $invite->getTokenVerifierHash());
        $this->assertSame($expiration, $invite->getExpiration());
        $this->assertTrue($invite->isUsed());
        $this->assertSame($newRole, $invite->getRole());
    }
}
