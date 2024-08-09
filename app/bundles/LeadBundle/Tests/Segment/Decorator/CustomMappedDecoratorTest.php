<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomMappedDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::getField
     */
    public function testGetField(): void
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
    public function testGetTable(): void
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
    public function testGetQueryType(): void
    {
        $customMappedDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'dnc_bounced',
        ]);

        $this->assertSame('mautic.lead.query.builder.special.dnc', $customMappedDecorator->getQueryType($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::getForeignContactColumn
     */
    public function testGetForeignContactColumn(): void
    {
        $customMappedDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'lead_email_read_count',
        ]);

        $this->assertSame('lead_id', $customMappedDecorator->getForeignContactColumn($contactSegmentFilterCrate));
    }

    /**
     * @return CustomMappedDecorator
     */
    private function getDecorator()
    {
        $contactSegmentFilterOperator   = $this->createMock(ContactSegmentFilterOperator::class);
        $dispatcherMock                 = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary($dispatcherMock);

        return new CustomMappedDecorator($contactSegmentFilterOperator, $contactSegmentFilterDictionary);
    }
}
