<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;
use PHPUnit\Framework\TestCase;

final class LoginEventTest extends TestCase
{
    public function testGetUser(): void
    {
        $user  = new User();
        $event = new LoginEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
