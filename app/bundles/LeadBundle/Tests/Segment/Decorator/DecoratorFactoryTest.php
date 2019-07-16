<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\CompanyDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        $filterDecoratorInterface       = $this->createMock(FilterDecoratorInterface::class);

        $eventDispatcherMock            = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary($eventDispatcherMock);
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $companyDecorator               = $this->createMock(CompanyDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);

        $decoratorFactory = new DecoratorFactory(
            $contactSegmentFilterDictionary,
            $baseDecorator,
            $customMappedDecorator,
            $dateOptionFactory,
            $companyDecorator,
            $eventDispatcherMock);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'date',
        ]);

        $dateOptionFactory->expects($this->once())
            ->method('getDateOption')
            ->with($contactSegmentFilterCrate)
            ->willReturn($filterDecoratorInterface);

        $eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(LeadEvents::SEGMENT_ON_DECORATOR_DELEGATE, $this->isType('object'))
            ->willReturn(null);

        $filterDecorator = $decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

        $this->assertInstanceOf(FilterDecoratorInterface::class, $filterDecorator);
        $this->assertSame($filterDecoratorInterface, $filterDecorator);
    }

    /**
     * @return DecoratorFactory
     */
    private function getDecoratorFactory()
    {
        $eventDispatcherMock            = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary = new ContactSegmentFilterDictionary($eventDispatcherMock);
        $baseDecorator                  = $this->createMock(BaseDecorator::class);
        $customMappedDecorator          = $this->createMock(CustomMappedDecorator::class);
        $companyDecorator               = $this->createMock(CompanyDecorator::class);
        $dateOptionFactory              = $this->createMock(DateOptionFactory::class);

        return new DecoratorFactory(
            $contactSegmentFilterDictionary,
            $baseDecorator,
            $customMappedDecorator,
            $dateOptionFactory,
            $companyDecorator,
            $eventDispatcherMock);
    }
}
