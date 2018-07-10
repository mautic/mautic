<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateDefault;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateDefaultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\Date\Other\DateDefault::getParameterValue
     */
    public function testGetParameterValue()
    {
        $dateDecorator             = $this->createMock(DateDecorator::class);
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateDefault($dateDecorator, '2018-03-02 01:02:03');

        $this->assertEquals('2018-03-02 01:02:03', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
