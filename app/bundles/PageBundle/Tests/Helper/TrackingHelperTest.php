<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Helper\TrackingHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class TrackingHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CoreParametersHelper|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $coreParametersHelper;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var ContactTracker|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->requestStack         = $this->createMock(RequestStack::class);
        $this->contactTracker       = $this->createMock(ContactTracker::class);
    }

    public function testGetSession()
    {
        $input = [
            'test1',
            'test2',
        ];

        $trackingHelper = $this->getTrackingHelper();
        $trackingHelper->updateSession([$input[0]]);
        $trackingHelper->updateSession([$input[1]]);

        //without remove param
        $output = $trackingHelper->getSession();

        $this->assertSame($input, $output);

        // with remove
        $output = $trackingHelper->getSession(true);

        $this->assertSame($input, $output);

        $this->assertEmpty($trackingHelper->getSession());
    }

    private function getTrackingHelper(): TrackingHelper
    {
        return new TrackingHelper($this->coreParametersHelper, $this->requestStack, $this->contactTracker);
    }
}
