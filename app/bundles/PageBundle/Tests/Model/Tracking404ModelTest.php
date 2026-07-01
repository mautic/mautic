<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\Tracking404Model;

final class Tracking404ModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ContactTracker
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockContactTracker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CoreParametersHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $mockCoreParametersHelper;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->mockContactTracker = $this->createMock(ContactTracker::class);

        $this->lead = new Lead();
    }

    public function testIsTrackableIfTracking404OptionEnabled(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(true);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->createStub(PageModel::class));
        $this->assertFalse($tracking404Model->isTrackable());
    }

    public function testIsTrackableIfTracking404OptionDisable(): void
    {
        $this->mockCoreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_track_404_anonymous')
            ->willReturn(false);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->createStub(PageModel::class));
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

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->createStub(PageModel::class));
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

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->createStub(PageModel::class));
        $this->assertFalse($tracking404Model->isTrackable());
    }
}
