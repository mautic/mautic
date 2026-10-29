<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;

final class LoginEventTest extends TestCase
{
    public function testGetUser(): void
    {
        $user  = new User();
        $event = new LoginEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
