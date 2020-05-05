<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;

class CustomMappedDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::getField
     */
    public function testGetField()
    {
        $customMappedDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'lead_email_read_count',
        ]);

        $this->assertSame('open_count', $customMappedDecorator->getField($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::getTable
     */
    public function testGetTable()
    {
        $customMappedDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'lead_email_read_count',
        ]);

        $this->assertSame(MAUTIC_TABLE_PREFIX.'email_stats', $customMappedDecorator->getTable($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::getQueryType
     */
    public function testGetQueryType()
    {
        $customMappedDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'dnc_bounced',
        ]);

        $this->assertSame('mautic.lead.query.builder.special.dnc', $customMappedDecorator->getQueryType($contactSegmentFilterCrate));
    }

    /**
     * @return CustomMappedDecorator
     */
    private function getDecorator()
    {
        $contactSegmentFilterOperator   = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary();

        return new CustomMappedDecorator($contactSegmentFilterOperator, $contactSegmentFilterDictionary);
    }
}
