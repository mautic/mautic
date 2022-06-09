<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collector\FieldCollector;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\FormEvents;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class FieldCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject(): void
    {
        $dispatcher = new class() extends EventDispatcher {
            
            public int $dispatchMethodCallCounter = 0;

            /**
             * @param FieldCollectEvent $event
             */
            public function dispatch($eventName, Event $event = null)
            {
                ++$this->dispatchMethodCallCounter;

                Assert::assertSame(FormEvents::ON_FIELD_COLLECT, $eventName);
                Assert::assertInstanceOf(FieldCollectEvent::class, $event);
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
