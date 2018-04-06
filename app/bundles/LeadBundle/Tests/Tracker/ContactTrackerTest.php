<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Tracker;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingServiceInterface;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactTrackerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LeadRepository
     */
    private $leadRepositoryMock;

    /**
     * @var ContactTrackingServiceInterface
     */
    private $contactTrackingServiceMock;

    /**
     * @var DeviceTracker
     */
    private $deviceTrackerMock;

    /**
     * @var CorePermissions
     */
    private $securityMock;

    /**
     * @var Logger
     */
    private $loggerMock;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelperMock;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelperMock;

    /**
     * @var EventDispatcher
     */
    private $dispatcherMock;

    public function setUp()
    {
        $this->leadRepositoryMock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactTrackingServiceMock = $this->getMockBuilder(ContactTrackingServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deviceTrackerMock = $this->getMockBuilder(DeviceTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityMock = $this->getMockBuilder(CorePermissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityMock->method('isAnonymous')
            ->willReturn(true);

        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ipLookupHelperMock = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = new RequestStack();
        $request            = new Request();
        $this->requestStack->push($request);

        $this->coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcherMock = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSystemContactIsUsedOverTrackedContact()
    {
        $contactTracker = $this->getContactTracker();

        $lead1 = new Lead();
        $lead1->setEmail('lead1@test.com');
        $contactTracker->setTrackedContact($lead1);
        $this->assertEquals($lead1->getEmail(), $contactTracker->getContact()->getEmail());

        $lead2 = new Lead();
        $lead1->setEmail('lead2@test.com');
        $contactTracker->setSystemContact($lead2);
        $this->assertEquals($lead2->getEmail(), $contactTracker->getContact()->getEmail());
    }

    public function testContactIsTrackedByDevice()
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

    public function testContactIsTrackedByOldCookie()
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

    public function testContactIsTrackedByIp()
    {
        $contactTracker = $this->getContactTracker();

        $this->ipLookupHelperMock->expects($this->once())
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $this->leadRepositoryMock->expects($this->never())
            ->method('getFieldValues');

        $lead = new Lead();
        $lead->setEmail('test@test.com');

        $this->contactTrackingServiceMock->expects($this->once())
            ->method('getTrackedLead')
            ->willReturn(null);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->willReturn(true);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByIp')
            ->willReturn([$lead]);

        $contact = $contactTracker->getContact();

        $this->assertEquals('test@test.com', $contact->getEmail());
    }

    public function testNewContactIsCreated()
    {
        $contactTracker = $this->getContactTracker();

        $this->ipLookupHelperMock->expects($this->once())
            ->method('getIpAddress')
            ->willReturn(new IpAddress());

        $this->leadRepositoryMock->expects($this->once())
            ->method('getFieldValues');

        $this->contactTrackingServiceMock->expects($this->once())
            ->method('getTrackedLead')
            ->willReturn(null);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->willReturn(false);

        $this->leadRepositoryMock->expects($this->never())
            ->method('getLeadsByIp');

        $contact = $contactTracker->getContact();

        $this->assertEquals(true, $contact->isNewlyCreated());
    }

    public function testEventIsDispatchedWithChangeOfContact()
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

        $this->dispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->withConsecutive([LeadEvents::CURRENT_LEAD_CHANGED])
            ->willReturn(true);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->withConsecutive([LeadEvents::CURRENT_LEAD_CHANGED, $this->anything()])
            ->willReturn(true);

        $leadDevice1 = new LeadDevice();
        $leadDevice1->setTrackingId('abc123');
        $this->deviceTrackerMock->expects($this->at(0))
            ->method('getTrackedDevice')
            ->willReturn($leadDevice1);

        $leadDevice2 = new LeadDevice();
        $leadDevice2->setTrackingId('def456');
        $this->deviceTrackerMock->expects($this->at(2))
            ->method('getTrackedDevice')
            ->willReturn($leadDevice2);

        $contactTracker->setTrackedContact($lead);
        $contactTracker->setTrackedContact($lead2);
    }

    /**
     * @return ContactTracker
     */
    private function getContactTracker()
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
            $this->dispatcherMock
        );
    }
}
