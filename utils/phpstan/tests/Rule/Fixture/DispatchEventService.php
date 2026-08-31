<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatchEventService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function withRedundantEventName(MenuEvent $event): void
    {
        // the CoreEvents:: second argument is redundant and must be reported
        $this->dispatcher->dispatch($event, CoreEvents::BUILD_MENU);
    }

    public function withEventObjectOnly(MenuEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function withNonCoreEventName(MenuEvent $event): void
    {
        // a non-CoreEvents name is out of scope for this rule
        $this->dispatcher->dispatch($event, 'some.custom.event');
    }
}
