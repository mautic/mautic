<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collector\FieldCollector;
use Mautic\FormBundle\Event\FieldCollectEvent;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class FieldCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject(): void
    {
        $dispatcher                               = new class() extends EventDispatcher {
            public int $dispatchMethodCallCounter = 0;

            public function dispatch(object $event, string $eventName = null): object
            {
                ++$this->dispatchMethodCallCounter;

                \assert($event instanceof FieldCollectEvent);
                Assert::assertSame('contact', $event->getObject());

                return new FieldCollection();
            }
        };

        $fieldCollector  = new FieldCollector($dispatcher);
        $fieldCollector->getFields('contact');

        // Calling for the second time to ensure it's cached and the dispatcher is called only once.
        $fieldCollection = $fieldCollector->getFields('contact');

        Assert::assertEquals(1, $dispatcher->dispatchMethodCallCounter);
    }
}
