<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\Tracking404Model;

class Tracking404ModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockCoreParametersHelper;

    /**
     * @var ContactTracker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockContactTracker;

    /**
     * @var PageModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPageModel;

    /**
     * @var Lead
     */
    private $lead;

    public function setUp()
    {
        parent::setUp();
        $this->mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->mockContactTracker = $this->createMock(ContactTracker::class);

        $this->mockPageModel = $this->createMock(PageModel::class);

        $this->lead = new Lead();
    }

    public function testIsTrackableIfTracking404OptionEnabled()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('get')
            ->with('disable_tracking_404_anonymous')
            ->willReturn(true);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertFalse($tracking404Model->isTrackable());
    }

    public function testIsTrackableIfTracking404OptionDisable()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('get')
            ->with('disable_tracking_404_anonymous')
            ->willReturn(false);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertTrue($tracking404Model->isTrackable());
    }

    public function testIsTrackableForIdentifiedContacts()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('get')
            ->with('disable_tracking_404_anonymous')
            ->willReturn(true);

        $this->lead->setFirstname('identified');
        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertTrue($tracking404Model->isTrackable());
    }

    public function testIsTrackableForAnonymouse()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('get')
            ->with('disable_tracking_404_anonymous')
            ->willReturn(true);

        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $this->assertFalse($tracking404Model->isTrackable());
    }
}
