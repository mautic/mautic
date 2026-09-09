<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class EventModelTest extends TestCase
{
    /**
     * @var MockObject&EventRepository
     */
    private MockObject $eventRepositoryMock;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcherMock;

    private MockObject|EventModel $eventModel;

    protected function setUp(): void
    {
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->dispatcherMock      = $this->createMock(EventDispatcherInterface::class);

        $this->eventModel          = new EventModel(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CorePermissions::class),
            $this->dispatcherMock,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );

        $this->eventModel->autowireEventModel(
            $this->eventRepositoryMock,
            $this->createStub(CampaignRepository::class),
            $this->createStub(LeadEventLogRepository::class)
        );
    }

    public function testThatClonedEventsDoNotAttemptNullingParentInDeleteEvents(): void
    {
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

        $this->eventRepositoryMock->expects($this->once())
            ->method('nullEventRelationships')
            ->with([$idToDelete]);

        $this->eventRepositoryMock->expects($this->once())
            ->method('setEventsAsDeletedWithRedirect')
            ->with([
                [
                    'id'              => $idToDelete,
                    'redirectEvent'   => null,
                ],
            ]);

        $this->dispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteEvent([$idToDelete]));

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

        $this->dispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteEvent(['old1']));

        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testDeleteEventsByCampaignId(): void
    {
        /** @var EventModel&MockObject $mockModel */
        $mockModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'deleteEventsByEventIds'])
            ->getMock();

        $mockModel->autowireEventModel(
            $this->eventRepositoryMock,
            $this->createStub(CampaignRepository::class),
            $this->createStub(LeadEventLogRepository::class),
        );

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
