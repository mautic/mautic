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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PointModelTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private IpLookupHelper&MockObject $ipLookupHelper;
    private LeadModel&MockObject $leadModel;
    private ContactTracker&MockObject $contactTracker;
    private EntityManager&MockObject $em;
    private CorePermissions&MockObject $security;
    private EventDispatcherInterface&MockObject $dispatcher;
    private UrlGeneratorInterface&MockObject $router;
    private Translator&MockObject $translator;
    private UserHelper&MockObject $userHelper;
    private LoggerInterface&MockObject $mauticLogger;
    private CoreParametersHelper&MockObject $coreParametersHelper;
    private PointGroupModel&MockObject $pointGroupModel;
    private PointModel $pointModel;

    protected function setUp(): void
    {
        $this->requestStack         = $this->createMock(RequestStack::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->contactTracker       = $this->createMock(ContactTracker::class);
        $this->em                   = $this->createMock(EntityManager::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->router               = $this->createMock(RouterInterface::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->userHelper           = $this->createMock(UserHelper::class);
        $this->mauticLogger         = $this->createMock(LoggerInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->pointGroupModel      = $this->createMock(PointGroupModel::class);
        $this->pointModel           = new PointModel(
            $this->requestStack,
            $this->ipLookupHelper,
            $this->leadModel,
            $this->contactTracker,
            $this->em,
            $this->security,
            $this->dispatcher,
            $this->router,
            $this->translator,
            $this->userHelper,
            $this->mauticLogger,
            $this->coreParametersHelper,
            $this->pointGroupModel,
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
        $ip              = $this->createMock(IpAddress::class);
        $this->security->method('isAnonymous')->willReturn(true);
        $this->ipLookupHelper->method('getIpAddress')->willReturn($ip);

        $lead = $this->createMock(Lead::class);
        $lead->expects(self::once())
            ->method('adjustPoints')
            ->with($pointDelta);
        $lead->expects(self::once())
            ->method('addPointsChangeLogEntry')
            ->with(
                'url',
                $pointId.': '.$pointName,
                'hit',
                $pointDelta,
                $ip,
                $pointGroup
            );
        $eventDetails = $this->createMock(Hit::class);

        $repository = $this->createMock(PointRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(Point::class)
            ->willReturn($repository);

        $pointActionHelper = $this->createMock(PointActionHelper::class);
        $pointActionHelper->expects(self::once())
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
        $point->method('getRepeatable')->willReturn(true);
        $point->method('getType')->willReturn($type);
        $point->method('getId')->willReturn($pointId);
        $point->method('getName')->willReturn($pointName);
        $point->method('getProperties')->willReturn($pointProperties);
        $point->method('getDelta')->willReturn($pointDelta);
        $point->method('getGroup')->willReturn($pointGroup);

        $repository->expects(self::once())
            ->method('getPublishedByType')
            ->with($type)
            ->willReturn([$point]);
        $repository->expects(self::once())
            ->method('getCompletedLeadActions')
            ->willReturn([]);
        $repository->expects(self::never())
            ->method('saveEntities');
        $repository->expects(self::never())
            ->method('detachEntities');

        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event, string $eventName) use ($pointActionHelper, $type, $lead, $point): Event {
                if (PointEvents::POINT_ON_BUILD === $eventName) {
                    self::assertInstanceOf(PointBuilderEvent::class, $event);
                    self::assertEquals(new PointBuilderEvent($this->translator), $event);
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
                    self::assertEquals($pointActionEvent, $event);

                    return $pointActionEvent;
                }

                self::fail('Unknown event called: '.$eventName);
            });

        $this->leadModel->expects(self::once())
            ->method('saveEntity')
            ->with($lead);

        $this->pointModel->triggerAction($type, $eventDetails, null, $lead);
    }
}
