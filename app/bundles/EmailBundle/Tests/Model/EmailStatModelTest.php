<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailStatModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class EmailStatModelTest extends TestCase
{
    public function testSave(): void
    {
        /** @var MockObject&EntityManager */
        $entityManager = $this->createMock(EntityManager::class);

        /** @var MockObject&StatRepository */
        $statRepository = $this->createMock(StatRepository::class);

        $entityManager->method('getRepository')->willReturn($statRepository);

        $statRepository->expects($this->once())
            ->method('saveEntities')
            ->willReturnCallback(
                function (array $entities) {
                    Assert::assertCount(1, $entities);
                    Assert::assertInstanceOf(StatTest::class, $entities[0]);

                    // Emulate database adding the entity some autoincrement ID.
                    $entities[0]->setId('123');
                }
            );

        $dispatcher = new class extends EventDispatcher {
            public int $dispatchMethodCounter = 0;

            public function __construct()
            {
                parent::__construct();
            }

            public function dispatch(object $event, string $eventName = null): object
            {
                switch ($this->dispatchMethodCounter) {
                    case 0:
                        Assert::assertSame(EmailEvents::ON_EMAIL_STAT_PRE_SAVE, $eventName);
                        Assert::assertCount(1, $event->getStats());
                        Assert::assertNull($event->getStats()[0]->getId());
                        break;

                    case 1:
                        Assert::assertSame(EmailEvents::ON_EMAIL_STAT_POST_SAVE, $eventName);
                        Assert::assertCount(1, $event->getStats());
                        Assert::assertSame('123', $event->getStats()[0]->getId());
                        break;
                }
                ++$this->dispatchMethodCounter;

                return $event;
            }
        };

        $emailStatModel = new EmailStatModel($entityManager, $dispatcher);

        $emailStat = new StatTest();

        $emailStatModel->saveEntity($emailStat);

        Assert::assertSame(2, $dispatcher->dispatchMethodCounter);
    }
}

class StatTest extends Stat
{
    private ?string $id = null;

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
