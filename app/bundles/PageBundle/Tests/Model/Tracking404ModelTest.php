<?php

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\Tracking404Model;

class Tracking404ModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockContactTracker;

    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockCoreParametersHelper;

    /**
     * @var PageModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockPageModel;

    private \Mautic\LeadBundle\Entity\Lead $lead;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->mockContactTracker = $this->createMock(ContactTracker::class);

        $this->mockPageModel = $this->createMock(PageModel::class);

        $this->lead = new Lead();
    }

    public function testIsTrackableIfTracking404OptionEnabled(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(true);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertFalse($tracking404Model->isTrackable());
    }

    public function testIsTrackableIfTracking404OptionDisable(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(false);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertTrue($tracking404Model->isTrackable());
    }

    public function testIsTrackableForIdentifiedContacts(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(true);

        $this->lead->setFirstname('identified');
        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertTrue($tracking404Model->isTrackable());
    }

    public function testIsTrackableForAnonymouse(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(true);

        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertFalse($tracking404Model->isTrackable());
    }
}
