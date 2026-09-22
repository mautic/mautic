<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Event;

use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;

final class RegisterScopesEventTest extends TestCase
{
    public function testGetScopes(): void
    {
        $event  = new RegisterScopesEvent();
        $this->assertEmpty($event->getScopes());
    }

    public function testAddScope(): void
    {
        $event  = new RegisterScopesEvent();
        $event->addScope('address');
        $this->assertSame(['address'], $event->getScopes());
    }

    public function testAddScopes(): void
    {
        $event  = new RegisterScopesEvent();
        $event->addScopes(['address', 'phone']);
        $this->assertSame(['address', 'phone'], $event->getScopes());
    }
}
