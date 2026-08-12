<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Helper\PointActionHelper;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\PointRepository;
use Mautic\PointBundle\Event\PointActionEvent;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class PointModelTest extends TestCase
{
    private IpLookupHelper&MockObject $ipLookupHelper;

    private LeadModel&MockObject $leadModel;

    private CorePermissions&MockObject $security;

    private EventDispatcherInterface&MockObject $dispatcher;

    private Translator&\PHPUnit\Framework\MockObject\Stub $translator;

    private PointRepository&MockObject $pointRepositoryMock;

    private PointModel $pointModel;

    protected function setUp(): void
    {
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->translator           = $this->createStub(Translator::class);
        $this->pointRepositoryMock      = $this->createMock(PointRepository::class);
        $this->pointModel           = new PointModel(
            $this->createStub(RequestStack::class),
            $this->ipLookupHelper,
            $this->leadModel,
            $this->createStub(ContactTracker::class),
            $this->createStub(EntityManager::class),
            $this->security,
            $this->dispatcher,
            $this->createStub(RouterInterface::class),
            $this->translator,
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(PointGroupModel::class),
            $this->pointRepositoryMock,
        );
    }

    public function testTriggerUrlHitWithCallbackObject(): void
    {
        $type            = 'url.hit';
        $pointId         = 98783;
        $pointName       = 'Point name';
        $pointProperties = ['property' => 'value'];
        $pointDelta      = 7;
        $pointGroup      = null;
        $ip              = $this->createStub(IpAddress::class);
        $this->security->expects($this->once())->method('isAnonymous')->willReturn(true);
        $this->ipLookupHelper->expects($this->once())->method('getIpAddress')->willReturn($ip);

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('adjustPoints')
            ->with($pointDelta);
        $lead->expects($this->once())
            ->method('addPointsChangeLogEntry')
            ->with(
                'url',
                $pointId.': '.$pointName,
                'hit',
                $pointDelta,
                $ip,
                $pointGroup
            );
        $eventDetails = $this->createStub(Hit::class);

        $pointActionHelper = $this->createMock(PointActionHelper::class);
        $pointActionHelper->expects($this->once())
            ->method('validateUrlHit')
            ->with(
                $eventDetails,
                [
                    'id'         => $pointId,
                    'type'       => $type,
                    'name'       => $pointName,
                    'properties' => $pointProperties,
                    'points'     => $pointDelta,
                ]
            )
            ->willReturn(true);

        $point = $this->createMock(Point::class);
        $point->expects($this->exactly(2))->method('getRepeatable')->willReturn(true);
        $point->expects($this->exactly(4))->method('getType')->willReturn($type);
        $point->method('getId')->willReturn($pointId);
        $point->method('getName')->willReturn($pointName);
        $point->expects($this->once())->method('getProperties')->willReturn($pointProperties);
        $point->method('getDelta')->willReturn($pointDelta);
        $point->expects($this->once())->method('getGroup')->willReturn($pointGroup);

        $this->pointRepositoryMock->expects($this->once())
            ->method('getPublishedByType')
            ->with($type)
            ->willReturn([$point]);
        $this->pointRepositoryMock->expects($this->once())
            ->method('getCompletedLeadActions')
            ->willReturn([]);
        $this->pointRepositoryMock->expects($this->never())
            ->method('saveEntities');
        $this->pointRepositoryMock->expects($this->never())
            ->method('detachEntities');

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventName) use ($pointActionHelper, $type, $lead, $point): Event {
                if (PointEvents::POINT_ON_BUILD === $eventName) {
                    $this->assertInstanceOf(PointBuilderEvent::class, $event);
                    $this->assertEquals(new PointBuilderEvent($this->translator), $event);
                    $event->addAction(
                        $type,
                        [
                            'callback' => [
                                $pointActionHelper,
                                'validateUrlHit',
                            ],
                            'group' => 'group',
                            'label' => 'label',
                        ],
                    );

                    return $event;
                }

                if (PointEvents::POINT_ON_ACTION === $eventName) {
                    $pointActionEvent = new PointActionEvent($point, $lead);
                    $this->assertEquals($pointActionEvent, $event);

                    return $pointActionEvent;
                }

                self::fail('Unknown event called: '.$eventName);
            });

        $this->leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);

        $this->pointModel->triggerAction($type, $eventDetails, null, $lead);
    }
}
