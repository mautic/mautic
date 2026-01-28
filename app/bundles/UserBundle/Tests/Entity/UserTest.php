<?php

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setUsername('testUser');
        $user->setPlainPassword('plainPass');
        $user->setCurrentPassword('currentPass');

        $user = unserialize(serialize($user));
        \assert($user instanceof User);

        $this->assertSame('testUser', $user->getUsername());
        $this->assertNull($user->getPlainPassword());
        $this->assertNull($user->getCurrentPassword());
    }

    public function testUserIsGuest(): void
    {
        $user = new User(true);
        $this->assertTrue($user->isGuest());
    }

    public function testUserIsNotGuest(): void
    {
        $user = new User();
        $this->assertFalse($user->isGuest());
    }
}
