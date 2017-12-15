<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model\Service\ContactTrackingService;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\Service\ContactTrackingService\ContactTrackingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContactTrackingServiceTest.
 */
final class ContactTrackingServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $cookieHelperMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $leadDeviceRepositoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $leadRepositoryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $requestStackMock;

    protected function setUp()
    {
        $this->cookieHelperMock         = $this->createMock(CookieHelper::class);
        $this->leadDeviceRepositoryMock = $this->createMock(LeadDeviceRepository::class);
        $this->leadRepositoryMock       = $this->createMock(LeadRepository::class);
        $this->requestStackMock         = $this->createMock(RequestStack::class);
    }

    public function testGetTrackedIdentifier()
    {
        // Parameters
        $trackingId = 'randomTrackingId';

        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertSame($trackingId, $contactTrackingService->getTrackedIdentifier());
    }

    public function testGetTrackedLeadNoRequest()
    {
        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn(null);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertNull($contactTrackingService->getTrackedLead());
    }

    public function testGetTrackedLeadNoTrackedIdentifier()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn(null);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertNull($contactTrackingService->getTrackedLead());
    }

    /**
     * Test no lead id found.
     */
    public function testGetTrackedLeadNoLeadId()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);
        $trackingId  = 'randomTrackingId';

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $this->cookieHelperMock->expects($this->at(1))
            ->method('getCookie')
            ->with($trackingId, null)
            ->willReturn(null);

        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mtc_id', null)
            ->willReturn(null);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertNull($contactTrackingService->getTrackedLead());
    }

    /**
     * Test lead id found in request but no lead entity found.
     */
    public function testGetTrackedLeadRequestLeadIdAndNoLeadFound()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);
        $trackingId  = 'randomTrackingId';
        $leadId      = 1;

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $this->cookieHelperMock->expects($this->at(1))
            ->method('getCookie')
            ->with($trackingId, null)
            ->willReturn(null);

        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mtc_id', null)
            ->willReturn($leadId);

        $this->leadRepositoryMock->expects($this->at(0))
            ->method('getEntity')
            ->with($leadId)
            ->willReturn(null);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertNull($contactTrackingService->getTrackedLead());
    }

    /**
     * Test lead id found in request and another device is already tracked and associated with lead.
     */
    public function testGetTrackedLeadRequestLeadIdAndAnotherDeviceAlreadyTracked()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);
        $trackingId  = 'randomTrackingId';
        $leadId      = 1;
        $leadMock    = $this->createMock(Lead::class);

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $this->cookieHelperMock->expects($this->at(1))
            ->method('getCookie')
            ->with($trackingId, null)
            ->willReturn(null);

        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mtc_id', null)
            ->willReturn($leadId);

        $this->leadRepositoryMock->expects($this->at(0))
            ->method('getEntity')
            ->with($leadId)
            ->willReturn($leadMock);
        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('isAnyLeadDeviceTracked')
            ->with($leadMock)
            ->willReturn(true);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertNull($contactTrackingService->getTrackedLead());
    }

    /**
     * Test lead id found in request and another device is not tracked and associated with lead.
     */
    public function testGetTrackedLeadRequestLeadIdAndAnotherDeviceNotTracked()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);
        $trackingId  = 'randomTrackingId';
        $leadId      = 1;
        $leadMock    = $this->createMock(Lead::class);

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $this->cookieHelperMock->expects($this->at(1))
            ->method('getCookie')
            ->with($trackingId, null)
            ->willReturn(null);

        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mtc_id', null)
            ->willReturn($leadId);

        $this->leadRepositoryMock->expects($this->at(0))
            ->method('getEntity')
            ->with($leadId)
            ->willReturn($leadMock);
        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('isAnyLeadDeviceTracked')
            ->with($leadMock)
            ->willReturn(false);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertSame($leadMock, $contactTrackingService->getTrackedLead());
    }

    /**
     * Test lead id found in request and another device is not tracked and associated with lead.
     */
    public function testGetTrackedLeadCookieLeadIdAndAnotherDeviceNotTracked()
    {
        // Parameters
        $requestMock = $this->createMock(Request::class);
        $trackingId  = 'randomTrackingId';
        $leadId      = 1;
        $leadMock    = $this->createMock(Lead::class);

        // __construct
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_session_id', null)
            ->willReturn($trackingId);

        $this->cookieHelperMock->expects($this->at(1))
            ->method('getCookie')
            ->with($trackingId, null)
            ->willReturn($leadId);

        $this->leadRepositoryMock->expects($this->at(0))
            ->method('getEntity')
            ->with($leadId)
            ->willReturn($leadMock);
        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('isAnyLeadDeviceTracked')
            ->with($leadMock)
            ->willReturn(false);

        $contactTrackingService = $this->getContactTrackingService();
        $this->assertSame($leadMock, $contactTrackingService->getTrackedLead());
    }

    /**
     * @return ContactTrackingService
     */
    private function getContactTrackingService()
    {
        return new ContactTrackingService(
            $this->cookieHelperMock,
            $this->leadDeviceRepositoryMock,
            $this->leadRepositoryMock,
            $this->requestStackMock
        );
    }
}
