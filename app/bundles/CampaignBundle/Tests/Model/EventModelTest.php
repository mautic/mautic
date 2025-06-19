<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventModelTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    private $entityManagerMock;

    /**
     * @var EventRepository|MockObject
     */
    private $eventRepositoryMock;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcherMock;

    private MockObject|EventModel $eventModel;

    protected function setUp(): void
    {
        $this->entityManagerMock   = $this->createMock(EntityManagerInterface::class);
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->dispatcherMock      = $this->createMock(EventDispatcherInterface::class);

        $this->eventModel          = new EventModel(
            $this->entityManagerMock,
            $this->createMock(CorePermissions::class),
            $this->dispatcherMock,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );
        $this->entityManagerMock
            ->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);
    }

    public function testThatClonedEventsDoNotAttemptNullingParentInDeleteEvents(): void
    {
        $this->entityManagerMock->expects($this->never())
            ->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);

        $this->eventRepositoryMock->expects($this->never())
            ->method('nullEventRelationships');

        $this->eventRepositoryMock->expects($this->never())
            ->method('setEventsAsDeletedWithRedirect');

        $currentEvents = [
            'new1',
            'new2',
            'new3',
        ];

        $deletedEvents = [
            ['id' => 'new1', 'redirectEvent' => null],
        ];

        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testThatItDeletesEventLogs(): void
    {
        $idToDelete = 'old1';

        $currentEvents = [
            'new1',
        ];

        $deletedEvents = [
            ['id' => 'new1', 'redirectEvent' => null],
            ['id' => $idToDelete, 'redirectEvent' => null],
        ];

        $eventRepositoryMock = $this->createMock(EventRepository::class);
        $entityManagerMock   = $this->createMock(EntityManager::class);

        $entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->willReturn($eventRepositoryMock);

        $eventRepositoryMock->expects($this->once())
            ->method('nullEventRelationships')
            ->with([$idToDelete]);

        $eventRepositoryMock->expects($this->once())
            ->method('setEventsAsDeletedWithRedirect')
            ->with([
                [
                    'id'              => $idToDelete,
                    'redirectEvent'   => null,
                ],
            ]);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteEvent([$idToDelete]), CampaignEvents::ON_EVENT_DELETE);

        // Use our local mocks instead of the class properties
        $this->eventModel->setEntityManager($entityManagerMock);
        $this->eventModel->setDispatcher($dispatcherMock);
        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testThatItDeletesEventLogsWithNewFormat(): void
    {
        $currentEvents = [
            'new1',
        ];

        $redirectEvent = $this->createMock(Event::class);
        $redirectEvent->method('getId')->willReturn(123);

        $deletedEvents = [
            ['id' => 'new1', 'redirectEvent' => null],
            [
                'id'                => 'old1',
                'redirectEvent'     => $redirectEvent,
            ],
        ];

        $this->eventRepositoryMock->expects($this->once())
            ->method('nullEventRelationships')
            ->with(['old1']);

        $this->eventRepositoryMock->expects($this->once())
            ->method('setEventsAsDeletedWithRedirect')
            ->with([
                [
                    'id'              => 'old1',
                    'redirectEvent'   => 123,
                ],
            ]);

        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $dispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_DELETE, new DeleteEvent(['old1']));

        $this->eventModel->setEntityManager($this->entityManagerMock);
        $this->eventModel->setDispatcher($dispatcherMock);
        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testDeleteEventsByCampaignId(): void
    {
        /** @var EventModel&MockObject */
        $mockModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'deleteEventsByEventIds'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->eventRepositoryMock);

        $campaignEvents = ['1', '2', '3'];

        $this->eventRepositoryMock->expects($this->once())
            ->method('getCampaignEventIds')
            ->with(1)
            ->willReturn($campaignEvents);

        $mockModel->expects($this->once())->method('deleteEventsByEventIds')
            ->with($campaignEvents);

        $mockModel->deleteEventsByCampaignId(1);
    }
}
