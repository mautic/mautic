<?php

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testUserIsGuest()
    {
        $user = new User(true);
        $this->assertTrue($user->isGuest());
    }

    public function testUserIsNotGuest()
    {
        $user = new User();
        $this->assertFalse($user->isGuest());
    }

    public function testSetAutomaticPassword()
    {
        $user = new User();
        $user->setAutomaticPassword(true);

        $this->assertTrue($user->getAutomaticPassword());
    }
}
