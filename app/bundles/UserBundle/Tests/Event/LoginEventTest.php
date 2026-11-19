<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;

final class LoginEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUser(): void
    {
        $user  = $this->createStub(User::class);
        $event = new LoginEvent($user);

        $this->assertEquals($user, $event->getUser());
    }
}
