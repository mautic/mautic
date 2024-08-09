<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Tracker;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingServiceInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactTrackerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LeadRepository
     */
    private MockObject $leadRepositoryMock;

    /**
     * @var MockObject|ContactTrackingServiceInterface
     */
    private MockObject $contactTrackingServiceMock;

    /**
     * @var MockObject|DeviceTracker
     */
    private MockObject $deviceTrackerMock;

    /**
     * @var MockObject|CorePermissions
     */
    private MockObject $securityMock;

    /**
     * @var MockObject|Logger
     */
    private MockObject $loggerMock;

    /**
     * @var MockObject|IpLookupHelper
     */
    private MockObject $ipLookupHelperMock;

    private RequestStack $requestStack;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private MockObject $coreParametersHelperMock;

    /**
     * @var MockObject|EventDispatcher
     */
    private MockObject $dispatcherMock;

    /**
     * @var MockObject|FieldModel
     */
    private MockObject $leadFieldModelMock;

    protected function setUp(): void
    {
        $this->leadRepositoryMock         = $this->createMock(LeadRepository::class);
        $this->contactTrackingServiceMock = $this->createMock(ContactTrackingServiceInterface::class);
        $this->deviceTrackerMock          = $this->createMock(DeviceTracker::class);
        $this->securityMock               = $this->createMock(CorePermissions::class);
        $this->coreParametersHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->dispatcherMock             = $this->createMock(EventDispatcher::class);
        $this->leadFieldModelMock         = $this->createMock(FieldModel::class);
        $this->loggerMock                 = $this->createMock(Logger::class);
        $this->ipLookupHelperMock         = $this->createMock(IpLookupHelper::class);
        $this->requestStack               = new RequestStack();

        $this->securityMock->method('isAnonymous')
            ->willReturn(true);

        $this->requestStack->push(new Request());
    }

    public function testSystemContactIsUsedOverTrackedContact(): void
    {
        $contactTracker = $this->getContactTracker();

        $this->leadRepositoryMock->expects($this->any())
            ->method('getFieldValues')
            ->willReturn([]);

        $lead1 = new Lead();
        $lead1->setEmail('lead1@test.com');
        $contactTracker->setTrackedContact($lead1);
        $this->assertEquals($lead1->getEmail(), $contactTracker->getContact()->getEmail());

        $lead2 = new Lead();
        $lead1->setEmail('lead2@test.com');
        $contactTracker->setSystemContact($lead2);
        $this->assertEquals($lead2->getEmail(), $contactTracker->getContact()->getEmail());
    }

    public function testContactIsTrackedByDevice(): void
    {
        $contactTracker = $this->getContactTracker();

        $this->leadRepositoryMock->expects($this->once())
            ->method('getFieldValues')
            ->willReturn(
                [
                    'core' => [
                        'email' => [
                            'alias' => 'email',
                            'type'  => 'email',
                            'value' => 'test@test.com',
                        ],
                    ],
                ]
            );

        $device = new LeadDevice();
        $lead   = new Lead();
        $device->setLead($lead);

        $this->deviceTrackerMock->method('getTrackedDevice')
            ->willReturn($device);

        $contact = $contactTracker->getContact();

        $this->assertEquals('test@test.com', $contact->getFieldValue('email'));
    }

    public function testContactIsTrackedByOldCookie(): void
    {
        $contactTracker = $this->getContactTracker();

        $this->leadRepositoryMock->expects($this->never())
            ->method('getFieldValues');

        $lead = new Lead();
        $lead->setEmail('test@test.com');

        $this->contactTrackingServiceMock->expects($this->once())
            ->method('getTrackedLead')
            ->willReturn($lead);

        $contact = $contactTracker->getContact();

        $this->assertEquals('test@test.com', $contact->getEmail());
    }

    public function testContactIsTrackedByIp(): void
    {
        $contactTracker = $this->getContactTracker();

        $this->ipLookupHelperMock->expects($this->exactly(2))
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $this->leadRepositoryMock->expects($this->never())
            ->method('getFieldValues');

        $lead = new Lead();
        $lead->setEmail('test@test.com');

        $this->contactTrackingServiceMock->expects($this->once())
            ->method('getTrackedLead')
            ->willReturn(null);

        $this->coreParametersHelperMock->expects($this->any())
            ->method('get')
            ->willReturn(true);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByIp')
            ->willReturn([$lead]);

        $contact = $contactTracker->getContact();

        $this->assertEquals('test@test.com', $contact->getEmail());
    }

    public function testNewContactIsCreated(): void
    {
        $contactTracker = $this->getContactTracker();

        $this->leadRepositoryMock->expects($this->once())
            ->method('getFieldValues')
            ->willReturn([]);

        $this->ipLookupHelperMock->expects($this->exactly(2))
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $this->contactTrackingServiceMock->expects($this->once())
            ->method('getTrackedLead')
            ->willReturn(null);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->willReturn(false);

        $this->leadRepositoryMock->expects($this->never())
            ->method('getLeadsByIp');
        $this->leadFieldModelMock->expects($this->any())->method('getFieldListWithProperties')->willReturn([]);

        $contact = $contactTracker->getContact();
        $this->assertEquals(true, $contact->isNewlyCreated());
    }

    public function testEventIsDispatchedWithChangeOfContact(): void
    {
        $contactTracker = $this->getContactTracker();

        $device = new LeadDevice();
        $device->setTrackingId('abc123');

        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->method('getId')
            ->willReturn(1);

        $lead2 = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead2->method('getId')
            ->willReturn(2);

        $leadDevice1 = new LeadDevice();
        $leadDevice2 = new LeadDevice();

        $leadDevice1->setTrackingId('abc123');
        $leadDevice2->setTrackingId('def456');

        $this->deviceTrackerMock->method('getTrackedDevice')
            ->willReturnOnConsecutiveCalls($leadDevice1, $leadDevice2);

        $this->dispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->with(LeadEvents::CURRENT_LEAD_CHANGED)
            ->willReturn(true);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(new LeadChangeEvent($lead, 'def456', $lead2, null), LeadEvents::CURRENT_LEAD_CHANGED)
            ->willReturn(new \stdClass());

        $contactTracker->setTrackedContact($lead);
        $contactTracker->setTrackedContact($lead2);
    }

    private function getContactTracker(): ContactTracker
    {
        return new ContactTracker(
            $this->leadRepositoryMock,
            $this->contactTrackingServiceMock,
            $this->deviceTrackerMock,
            $this->securityMock,
            $this->loggerMock,
            $this->ipLookupHelperMock,
            $this->requestStack,
            $this->coreParametersHelperMock,
            $this->dispatcherMock,
            $this->leadFieldModelMock
        );
    }
}
