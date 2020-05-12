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
use Mautic\LeadBundle\Segment\Decorator\CompanyDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DecoratorFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
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
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $companyDecorator               = $this->createMock(CompanyDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);
        $filterDecoratorInterface       = $this->createMock(FilterDecoratorInterface::class);
        $translator                     = $this->createMock(TranslatorInterface::class);
        $eventDispatcher                = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary($translator, $eventDispatcher);

        $decoratorFactory = new DecoratorFactory($contactSegmentFilterDictionary, $baseDecorator, $customMappedDecorator, $dateOptionFactory, $companyDecorator);

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
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $companyDecorator               = $this->createMock(CompanyDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);
        $translator                     = $this->createMock(TranslatorInterface::class);
        $eventDispatcher                = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary($translator, $eventDispatcher);

        return new DecoratorFactory($contactSegmentFilterDictionary, $baseDecorator, $customMappedDecorator, $dateOptionFactory, $companyDecorator);
    }
}
