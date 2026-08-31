<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CoreSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\SecurityEvents;

final class CoreSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame([
            MenuEvent::class                  => ['onBuildMenu', 9999],
            RouteEvent::class                 => ['onBuildRoute', 0],
            IconEvent::class                  => ['onFetchIcons', 9999],
            SecurityEvents::INTERACTIVE_LOGIN => ['onSecurityInteractiveLogin', 0],
        ], CoreSubscriber::getSubscribedEvents());
    }
}
