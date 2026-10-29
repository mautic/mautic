<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Test;

use Mautic\CoreBundle\Test\AttributeEventListenerRegistrar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

final class AttributeEventListenerRegistrarTest extends TestCase
{
    public function testRegistersMethodUnderExplicitEventName(): void
    {
        $listener = new class() {
            #[AsEventListener(event: 'test.event')]
            public function onTestEvent(object $event): void
            {
            }
        };

        $dispatcher = new EventDispatcher();
        AttributeEventListenerRegistrar::register($dispatcher, $listener);

        $listeners = $dispatcher->getListeners('test.event');
        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0][0]);
        $this->assertSame('onTestEvent', $listeners[0][1]);
    }

    public function testInfersEventNameFromFirstParameterType(): void
    {
        $listener = new class() {
            public bool $called = false;

            #[AsEventListener]
            public function onEvent(Event $event): void
            {
                $this->called = true;
            }
        };

        $dispatcher = new EventDispatcher();
        AttributeEventListenerRegistrar::register($dispatcher, $listener);
        $dispatcher->dispatch(new Event());

        $this->assertTrue($listener->called);
    }

    public function testRegistersClassLevelAttribute(): void
    {
        $listener = new #[AsEventListener(
            event: Event::class,
            method: 'handle'
        )] class() {
            public bool $called = false;

            public function handle(Event $event): void
            {
                $this->called = true;
            }
        };

        $dispatcher = new EventDispatcher();
        AttributeEventListenerRegistrar::register($dispatcher, $listener);
        $dispatcher->dispatch(new Event());

        $this->assertTrue($listener->called);
    }

    public function testHonorsListenerPriority(): void
    {
        $listener = new class() {
            /**
             * @var string[]
             */
            public array $calls = [];

            #[AsEventListener(
                event: Event::class,
                priority: 0
            )]
            public function low(Event $event): void
            {
                $this->calls[] = 'low';
            }

            #[AsEventListener(
                event: Event::class,
                priority: 10
            )]
            public function high(Event $event): void
            {
                $this->calls[] = 'high';
            }
        };

        $dispatcher = new EventDispatcher();
        AttributeEventListenerRegistrar::register($dispatcher, $listener);
        $dispatcher->dispatch(new Event());

        $this->assertSame(['high', 'low'], $listener->calls);
    }

    public function testThrowsWhenEventCannotBeInferred(): void
    {
        $listener = new class() {
            #[AsEventListener]
            public function onEvent(): void
            {
            }
        };

        $this->expectException(\LogicException::class);

        AttributeEventListenerRegistrar::register(new EventDispatcher(), $listener);
    }
}
