<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Event;

use Mautic\OpenIdBundle\Event\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;

final class RegisterScopesEventTest extends TestCase
{
    public function testGetScopes(): void
    {
        $event  = new RegisterScopesEvent();
        self::assertEmpty($event->getScopes());
    }

    public function testAddScope(): void
    {
        $event  = new RegisterScopesEvent();
        $event->addScope('address');
        self::assertSame(['address'], $event->getScopes());
    }

    public function testAddScopes(): void
    {
        $event  = new RegisterScopesEvent();
        $event->addScopes(['address', 'phone']);
        self::assertSame(['address', 'phone'], $event->getScopes());
    }
}
