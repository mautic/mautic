<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Model;

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
    }

    public function testThatClonedEventsDoNotAttemptNullingParentInDeleteEvents(): void
    {
        $this->entityManagerMock->expects($this->never())
            ->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);

        $currentEvents = [
            'new1',
            'new2',
            'new3',
        ];

        $deletedEvents = [
            'new1',
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
            'new1',
            $idToDelete,
        ];

        $this->entityManagerMock->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);

        $this->eventRepositoryMock->expects($this->once())
            ->method('nullEventRelationships')
            ->with([$idToDelete]);

        $this->eventRepositoryMock->expects($this->once())
            ->method('setEventsAsDeleted')
            ->with([1 => $idToDelete]);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteEvent([$idToDelete]), CampaignEvents::ON_EVENT_DELETE);

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
