<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailStatModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EmailStatModelTest extends TestCase
{
    public function testSave(): void
    {
        $entityManager = new class() extends EntityManager {
            public function __construct()
            {
            }

            public function getRepository($entityName)
            {
                Assert::assertSame(Stat::class, $entityName);

                return new class() extends StatRepository {
                    public function __construct()
                    {
                    }

                    public function saveEntities($entities)
                    {
                        Assert::assertCount(1, $entities);
                        Assert::assertInstanceOf(Stat::class, $entities[0]);

                        // Emulate database adding the entity some autoincrement ID.
                        $entities[0]->id = 123;
                    }
                };
            }
        };

        $dispatcher                       = new class() extends EventDispatcher {
            public $dispatchMethodCounter = 0;

            public function __construct()
            {
            }

            /**
             * @var EmailStatEvent
             */
            public function dispatch($eventName, ?Event $event = null)
            {
                switch ($this->dispatchMethodCounter) {
                    case 0:
                        Assert::assertSame(EmailEvents::ON_EMAIL_STAT_PRE_SAVE, $eventName);
                        Assert::assertCount(1, $event->getStats());
                        Assert::assertNull($event->getStats()[0]->id);
                        break;

                    case 1:
                        Assert::assertSame(EmailEvents::ON_EMAIL_STAT_POST_SAVE, $eventName);
                        Assert::assertCount(1, $event->getStats());
                        Assert::assertSame(123, $event->getStats()[0]->id);
                        break;
                }
                ++$this->dispatchMethodCounter;
            }
        };

        $emailStatModel = new EmailStatModel($entityManager, $dispatcher);

        $emailStat = new class() extends Stat {
            // Making the id property public to make it mutable by the repository fake.
            public $id;
        };

        $emailStatModel->saveEntity($emailStat);

        Assert::assertSame(2, $dispatcher->dispatchMethodCounter);
    }
}
