<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\EventListener\PointSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\PointRepository;
use Mautic\PointBundle\Model\PointModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

final class PointSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var MockObject|EntityManager
     */
    private $em;

    /**
     * @var MockObject|Session
     */
    protected $session;

    /**
     * @var LeadModel|MockObject
     */
    private $leadModel;

    /**
     * @var MauticFactory|MockObject
     */
    protected $mauticFactory;

    /**
     * @var ContactTracker|MockObject
     */
    private $contactTracker;

    /**
     * @var Translator|MockObject
     */
    private $translator;

    /**
     * @var CorePermissions|MockObject
     */
    private $security;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcher;

    /**
     * @var PointModel|MockObject
     */
    private $pointModel;

    /**
     * @var PointSubscriber|MockObject
     */
    private $pointSubscriber;

    /**
     * @var PointRepository|MockObject
     */
    private $pointRepositoryMock;

    /**
     * @var Lead|MockObject
     */
    private $leadMock;

    /**
     * @var Email|MockObject
     */
    private $emailMock;

    /**
     * @var Stat|MockObject
     */
    private $eventDetailsMock;

    /**
     * @var Category|MockObject
     */
    private $categoryMock;

    /**
     * @var IpAddress|MockObject
     */
    private $ipAddress;

    protected function setup(): void
    {
        parent::setUp();
        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->em             = $this->createMock(EntityManager::class);
        $this->session        = $this->createMock(Session::class);
        $this->leadModel      = $this->createMock(LeadModel::class);
        $this->mauticFactory  = $this->createMock(MauticFactory::class);
        $this->contactTracker = $this->createMock(ContactTracker::class);
        $this->translator     = $this->createMock(Translator::class);
        $this->security       = $this->createMock(CorePermissions::class);
        $this->dispatcher     = new EventDispatcher();

        $this->pointModel = $this->getMockBuilder(PointModel::class)
            ->setConstructorArgs([
                $this->session,
                $this->ipLookupHelper,
                $this->leadModel,
                $this->mauticFactory,
                $this->contactTracker,
            ])
            ->onlyMethods([])
            ->getMock();
        $this->pointModel->setEntityManager($this->em);
        $this->pointModel->setTranslator($this->translator);
        $this->pointModel->setSecurity($this->security);
        $this->pointModel->setDispatcher($this->dispatcher);

        $this->security
            ->method('isAnonymous')
            ->willReturn(true);

        $this->pointSubscriber = $this->getMockBuilder(PointSubscriber::class)
            ->setConstructorArgs([$this->pointModel, $this->em])
            ->onlyMethods([])
            ->getMock();

        $this->pointRepositoryMock = $this->createMock(PointRepository::class);
        $this->leadMock            = $this->createMock(Lead::class);
        $this->emailMock           = $this->createMock(Email::class);
        $this->eventDetailsMock    = $this->createMock(Stat::class);
        $this->categoryMock        = $this->createMock(Category::class);
        $this->ipAddress           = $this->createMock(IpAddress::class);

        $this->ipLookupHelper
            ->method('getIpAddress')
            ->willReturn($this->ipAddress);

        $this->dispatcher->addSubscriber($this->pointSubscriber);

        $this->em
            ->method('getRepository')
            ->with('MauticPointBundle:Point')
            ->willReturn($this->pointRepositoryMock);
    }

    public function testOnEmailOpenRepeatableActionWithCategory(): void
    {
        $pointMock1 = $this->createMock(Point::class);
        $pointMock1
            ->method('getType')
            ->willReturn('email.open');
        $pointMock1
            ->method('getRepeatable')
            ->willReturn(true);
        $pointMock1
            ->method('convertToArray')
            ->willReturn([
                'properties' => [
                    'emails'     => [],
                    'categories' => [
                        0 => 21,
                    ],
                ],
            ]);

        $pointMock2 = $this->createMock(Point::class);
        $pointMock2
            ->method('getType')
            ->willReturn('email.open');
        $pointMock2
            ->method('getRepeatable')
            ->willReturn(true);
        $pointMock2
            ->method('convertToArray')
            ->willReturn([
                'properties' => [
                    'emails'     => [],
                    'categories' => [
                        0 => 22,
                    ],
                ],
            ]);

        $this->pointRepositoryMock
            ->method('getPublishedByType')
            ->with('email.open')
            ->willReturn([$pointMock1, $pointMock2]);
        $this->pointRepositoryMock
            ->method('getCompletedLeadActions')
            ->willReturn([]);

        $this->contactTracker
            ->method('getContact')
            ->willReturn($this->leadMock);

        $this->leadMock
            ->method('getId')
            ->willReturn(1);

        $this->eventDetailsMock
            ->method('getEmail')
            ->willReturn($this->emailMock);

        $this->emailMock
            ->method('getCategory')
            ->willReturn($this->categoryMock);

        $this->categoryMock
            ->method('getId')
            ->willReturn(21);

        // Expect one adjust points, only $pointMock1 should pass
        $this->leadMock->expects($this->once())
            ->method('adjustPoints');

        $event = new EmailOpenEvent($this->eventDetailsMock, new Request([], []), false);
        $this->pointSubscriber->onEmailOpen($event);
    }

    public function testOnEmailOpenShouldUpdatePoints(): void
    {
        $pointMockProperties = [
            'emails' => [
                0 => '3',
            ],
            'categories'  => [],
            'triggerMode' => 'internalId',
        ];

        $pointMock = $this->createMock(Point::class);
        $pointMock
            ->method('getType')
            ->willReturn('email.open');
        $pointMock
            ->method('getRepeatable')
            ->willReturn(false);
        $pointMock
            ->method('convertToArray')
            ->willReturn([
                'properties' => $pointMockProperties,
            ]);
        $pointMock
            ->method('getProperties')
            ->willReturn($pointMockProperties);
        $pointMock
            ->method('getId')
            ->willReturn(9);

        $this->pointRepositoryMock
            ->method('getPublishedByType')
            ->with('email.open')
            ->willReturn([$pointMock]);
        $this->pointRepositoryMock
            ->method('getCompletedLeadActions')
            ->willReturn([]);

        $this->contactTracker
            ->method('getContact')
            ->willReturn($this->leadMock);

        $this->leadMock
            ->method('getId')
            ->willReturn(1);

        $this->eventDetailsMock
            ->method('getEmail')
            ->willReturn($this->emailMock);

        $this->emailMock
            ->method('getId')
            ->willReturn(3);

        $this->emailMock
            ->method('getCategory')
            ->willReturn($this->categoryMock);

        // Expect adjust points will be called once
        $this->leadMock->expects($this->once())
            ->method('adjustPoints');

        $event = new EmailOpenEvent($this->eventDetailsMock, new Request([], []), false);
        $this->pointSubscriber->onEmailOpen($event);
    }

    public function testOnEmailOpenShouldNotUpdatePointsWhenCompletedActionExists(): void
    {
        $pointMockProperties = [
            'emails' => [
                0 => '3',
            ],
            'categories'  => [],
            'triggerMode' => 'internalId',
        ];

        $pointMock = $this->createMock(Point::class);
        $pointMock
            ->method('getType')
            ->willReturn('email.open');
        $pointMock
            ->method('getRepeatable')
            ->willReturn(false);
        $pointMock
            ->method('convertToArray')
            ->willReturn([
                'properties' => $pointMockProperties,
            ]);
        $pointMock
            ->method('getProperties')
            ->willReturn($pointMockProperties);
        $pointMock
            ->method('getId')
            ->willReturn(9);

        $this->pointRepositoryMock
            ->method('getPublishedByType')
            ->with('email.open')
            ->willReturn([$pointMock]);
        $this->pointRepositoryMock
            ->method('getCompletedLeadActions')
            ->willReturn([
                9 => [
                    3 => [
                        'internal_id' => '3',
                    ],
                ],
            ]);

        $this->contactTracker
            ->method('getContact')
            ->willReturn($this->leadMock);

        $this->leadMock
            ->method('getId')
            ->willReturn(1);

        $this->eventDetailsMock
            ->method('getEmail')
            ->willReturn($this->emailMock);

        $this->emailMock
            ->method('getId')
            ->willReturn(3);

        $this->emailMock
            ->method('getCategory')
            ->willReturn($this->categoryMock);

        // Expect adjust points will not be called because action is already completed
        $this->leadMock->expects($this->never())
            ->method('adjustPoints');

        $event = new EmailOpenEvent($this->eventDetailsMock, new Request([], []), false);
        $this->pointSubscriber->onEmailOpen($event);
    }
}
