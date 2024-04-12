<?php

namespace Mautic\UserBundle\Tests\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;

class LoginEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUser(): void
    {
        $user  = $this->createMock(User::class);
        $event = new LoginEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
