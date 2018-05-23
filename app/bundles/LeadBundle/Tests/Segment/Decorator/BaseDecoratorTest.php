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
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;

class BaseDecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getField
     */
    public function testGetField()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'glue'     => 'and',
            'field'    => 'date_identified',
            'object'   => 'lead',
            'type'     => 'datetime',
            'filter'   => null,
            'display'  => null,
            'operator' => '!empty',
        ]);

        $this->assertSame('date_identified', $baseDecorator->getField($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getTable
     */
    public function testGetTableLead()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'object'   => 'lead',
        ]);

        $this->assertSame(MAUTIC_TABLE_PREFIX.'leads', $baseDecorator->getTable($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getTable
     */
    public function testGetTableCompany()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'object'   => 'company',
        ]);

        $this->assertSame(MAUTIC_TABLE_PREFIX.'companies', $baseDecorator->getTable($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getOperator
     */
    public function testGetOperatorEqual()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('=')
            ->willReturn('eq');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => '=',
        ]);

        $this->assertSame('eq', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getOperator
     */
    public function testGetOperatorStartsWith()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('startsWith')
            ->willReturn('startsWith');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'startsWith',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getOperator
     */
    public function testGetOperatorEndsWith()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('endsWith')
            ->willReturn('endsWith');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'endsWith',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getOperator
     */
    public function testGetOperatorContainsWith()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('contains')
            ->willReturn('contains');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'contains',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getQueryType
     */
    public function testGetQueryType()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $this->assertSame('mautic.lead.query.builder.basic', $baseDecorator->getQueryType($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterHolder
     */
    public function testGetParameterHolderSingle()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $this->assertSame(':argument', $baseDecorator->getParameterHolder($contactSegmentFilterCrate, 'argument'));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterHolder
     */
    public function testGetParameterHolderArray()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $argument = [
            'argument1',
            'argument2',
            'argument3',
        ];

        $expected = [
            ':argument1',
            ':argument2',
            ':argument3',
        ];
        $this->assertSame($expected, $baseDecorator->getParameterHolder($contactSegmentFilterCrate, $argument));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueBoolean()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'boolean',
            'filter' => '1',
        ]);

        $this->assertTrue($baseDecorator->getParameterValue($contactSegmentFilterCrate));

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'boolean',
            'filter' => '0',
        ]);

        $this->assertFalse($baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueNumber()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'number',
            'filter' => '1',
        ]);

        $this->assertSame(1.0, $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueLikeNoPercent()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueNotLike()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => '!like',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueLikeWithOnePercent()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => '%Test string',
        ]);

        $this->assertSame('%Test string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueLikeWithTwoPercent()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => '%Test string%',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueStartsWith()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'startsWith',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueEndsWith()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'endsWith',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueContains()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'contains',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueContainsShouldNotBeEscaped()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'contains',
            'filter'   => 'Test with % and special characters \% should not be escaped %',
        ]);

        $expected = '%Test with % and special characters \% should not be escaped %%';
        $this->assertSame($expected, $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueRegex()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'regexp',
            'filter'   => 'Test \\\s string',
        ]);

        $this->assertSame('Test \s string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::getParameterValue
     */
    public function testGetParameterValueNotRegex()
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => '!regexp',
            'filter'   => 'Test \\\s string',
        ]);

        $this->assertSame('Test \s string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @return BaseDecorator
     */
    private function getDecorator()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);

        return new BaseDecorator($contactSegmentFilterOperator);
    }
}
