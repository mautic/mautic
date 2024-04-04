<?php

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPlainPassword('test');
        $user->setCurrentPassword('test');
        $user->eraseCredentials();
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
