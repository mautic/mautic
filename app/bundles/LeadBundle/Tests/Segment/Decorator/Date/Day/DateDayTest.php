<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDay;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

class DateDayTest extends \PHPUnit\Framework\TestCase
{
    public function testGetParameterValue(): void
    {
        $dateDecorator    = $this->createMock(DateDecorator::class);
        $timezoneResolver = $this->createMock(TimezoneResolver::class);
        $dateTimeHelper   = new DateTimeHelper();
        $timezoneResolver->expects(self::once())->method('getDefaultDate')->willReturn($dateTimeHelper);

        $filter        = [
            'operator' => '=',
        ];
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);
        $dateOptionParameters      = new DateOptionParameters($contactSegmentFilterCrate, [], $timezoneResolver);

        $filterDecorator = new DateDay($dateDecorator, $dateOptionParameters);
        self::assertEquals(sprintf('%%-%%-%s%%', $dateTimeHelper->getDateTime()->format('d')), $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }
}
