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
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\Tracking404Model;
use Symfony\Component\HttpFoundation\Request;

class Tracking404ModelTest extends \PHPUnit_Framework_TestCase
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
        $this->mockPageModel->expects($this->any())
            ->method('hitPage')
            ->willThrowException(new \Exception());

        $this->lead = new Lead();
    }

    public function testIsTrackableIfTrackingEnabled()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('getParameter')
            ->with('disable_tracking_404')
            ->willReturn(true);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $tracking404Model->hitPage(new Page(), new Request());
    }

    public function testIsTrackableIfTrackingDisable()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('getParameter')
            ->with('disable_tracking_404')
            ->willReturn(false);
        $this->expectException(\Exception::class);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $tracking404Model->hitPage(new Page(), new Request());
    }

    public function testIsTrackableForIdentified()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('getParameter')
            ->with('disable_tracking_404')
            ->willReturn(true);

        $this->lead->setFirstname('identified');
        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $this->expectException(\Exception::class);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $tracking404Model->hitPage(new Page(), new Request());
    }

    public function testIsTrackableForAnonymouse()
    {
        $this->mockCoreParametersHelper->expects($this->at(0))
            ->method('getParameter')
            ->with('disable_tracking_404')
            ->willReturn(true);

        $this->mockContactTracker->expects($this->any())
            ->method('getContactByTrackedDevice')
            ->willReturn($this->lead);

        $tracking404Model = new Tracking404Model($this->mockCoreParametersHelper, $this->mockContactTracker, $this->mockPageModel);
        $tracking404Model->hitPage(new Page(), new Request());
    }
}
