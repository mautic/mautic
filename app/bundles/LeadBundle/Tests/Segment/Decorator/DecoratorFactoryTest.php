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
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;

class DecoratorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::getDecoratorForFilter
     */
    public function testBaseDecorator()
    {
        $decoratorFactory = $this->getDecoratorFactory();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'date_identified',
            'type'     => 'number',
        ]);

        $this->assertInstanceOf(BaseDecorator::class, $decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::getDecoratorForFilter
     */
    public function testCustomMappedDecorator()
    {
        $decoratorFactory = $this->getDecoratorFactory();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'hit_url_count',
            'type'     => 'number',
        ]);

        $this->assertInstanceOf(CustomMappedDecorator::class, $decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate));
    }

    /**
     * @covers \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::getDecoratorForFilter
     */
    public function testDateDecorator()
    {
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary();
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);
        $filterDecoratorInterface       = $this->createMock(FilterDecoratorInterface::class);

        $decoratorFactory = new DecoratorFactory($contactSegmentFilterDictionary, $baseDecorator, $customMappedDecorator, $dateOptionFactory);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'date',
        ]);

        $dateOptionFactory->expects($this->once())
            ->method('getDateOption')
            ->with($contactSegmentFilterCrate)
            ->willReturn($filterDecoratorInterface);

        $filterDecorator = $decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

        $this->assertInstanceOf(FilterDecoratorInterface::class, $filterDecorator);
        $this->assertSame($filterDecoratorInterface, $filterDecorator);
    }

    /**
     * @return DecoratorFactory
     */
    private function getDecoratorFactory()
    {
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary();
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);

        return new DecoratorFactory($contactSegmentFilterDictionary, $baseDecorator, $customMappedDecorator, $dateOptionFactory);
    }
}
