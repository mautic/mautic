<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collector\FieldCollector;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\FormEvents;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

final class FieldCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject(): void
    {
        $dispatcher                               = new class() extends EventDispatcher {
            public int $dispatchMethodCallCounter = 0;

            public function dispatch($eventName, Event $event = null)
            {
                ++$this->dispatchMethodCallCounter;

                \assert($event instanceof FieldCollectEvent);
                Assert::assertSame(FormEvents::ON_FIELD_COLLECT, $eventName);
                Assert::assertSame('contact', $event->getObject());

                return new FieldCollection();
            }
        };

        $fieldCollector  = new FieldCollector($dispatcher);
        $fieldCollection = $fieldCollector->getFields('contact');

        // Calling for the second time to ensure it's cached and the dispatcher is called only once.
        $fieldCollection = $fieldCollector->getFields('contact');

        Assert::assertInstanceOf(FieldCollection::class, $fieldCollection);
        Assert::assertEquals(1, $dispatcher->dispatchMethodCallCounter);
    }
}
