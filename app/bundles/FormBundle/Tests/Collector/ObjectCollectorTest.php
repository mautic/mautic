<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Collector\ObjectCollector;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ObjectCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject()
    {
        $dispatcher                           = new class() extends EventDispatcher {
            public $dispatchMethodCallCounter = 0;

            /**
             * @param ObjectCollectEvent $event
             */
            public function dispatch($eventName, Event $event = null)
            {
                ++$this->dispatchMethodCallCounter;

                Assert::assertSame(FormEvents::ON_OBJECT_COLLECT, $eventName);
                Assert::assertInstanceOf(ObjectCollectEvent::class, $event);

                return new ObjectCollection();
            }
        };

        $objectCollector  = new ObjectCollector($dispatcher);
        $objectCollection = $objectCollector->getObjects();

        // Calling for the second time to ensure it's cached and the dispatcher is called only once.
        $objectCollection = $objectCollector->getObjects();

        Assert::assertInstanceOf(ObjectCollection::class, $objectCollection);
        Assert::assertEquals(1, $dispatcher->dispatchMethodCallCounter);
    }
}
