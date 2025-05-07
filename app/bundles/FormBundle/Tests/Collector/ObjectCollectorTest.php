<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Collector\ObjectCollector;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ObjectCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject(): void
    {
        $dispatcher                               = new class extends EventDispatcher {
            public int $dispatchMethodCallCounter = 0;

            public function dispatch(object $event, string $eventName = null): object
            {
                ++$this->dispatchMethodCallCounter;

                Assert::assertInstanceOf(ObjectCollectEvent::class, $event);

                return new ObjectCollection();
            }
        };

        $objectCollector  = new ObjectCollector($dispatcher);
        $objectCollector->getObjects();

        // Calling for the second time to ensure it's cached and the dispatcher is called only once.
        $objectCollection = $objectCollector->getObjects();

        Assert::assertEquals(1, $dispatcher->dispatchMethodCallCounter);
    }
}
