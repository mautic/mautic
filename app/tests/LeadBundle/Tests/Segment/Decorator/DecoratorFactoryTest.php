<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\CompanyDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AllowMockObjectsWithoutExpectations]
final class DecoratorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcherMock;

    /**
     * @var MockObject&DateOptionFactory
     */
    private MockObject $dateOptionFactory;

    private DecoratorFactory $decoratorFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcherMock            = $this->createMock(EventDispatcherInterface::class);
        $contactSegmentFilterDictionary       = new ContactSegmentFilterDictionary($this->eventDispatcherMock);
        $this->dateOptionFactory              = $this->createMock(DateOptionFactory::class);
        $this->decoratorFactory               = new DecoratorFactory(
            $contactSegmentFilterDictionary,
            $this->createStub(BaseDecorator::class),
            $this->createStub(CustomMappedDecorator::class),
            $this->dateOptionFactory,
            $this->createStub(CompanyDecorator::class),
            $this->eventDispatcherMock);
    }

    public function testBaseDecorator(): void
    {
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'date_identified',
            'type'     => 'number',
        ]);

        $this->assertInstanceOf(
            BaseDecorator::class,
            $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate)
        );
    }

    public function testCustomMappedDecorator(): void
    {
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'field'    => 'hit_url_count',
            'type'     => 'number',
        ]);

        $this->assertInstanceOf(
            CustomMappedDecorator::class,
            $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate)
        );
    }

    public function testDateDecoratorWhenNoSubscriberProvidesDecorator(): void
    {
        $filterDecoratorInterface  = $this->createStub(FilterDecoratorInterface::class);
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => 'date']);

        $this->dateOptionFactory->expects($this->once())
            ->method('getDateOption')
            ->with($contactSegmentFilterCrate)
            ->willReturn($filterDecoratorInterface);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    function (LeadListFiltersDecoratorDelegateEvent $event) use ($contactSegmentFilterCrate): true {
                        $this->assertNotInstanceOf(FilterDecoratorInterface::class, $event->getDecorator());
                        $this->assertSame($contactSegmentFilterCrate, $event->getCrate());

                        return true;
                    }
                )
            );

        $this->assertSame(
            $filterDecoratorInterface,
            $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate)
        );
    }

    public function testDateDecoratorWhenSubscriberProvidesDecorator(): void
    {
        $filterDecoratorInterface  = $this->createStub(FilterDecoratorInterface::class);
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate(['type' => 'date']);

        $this->dateOptionFactory->expects($this->never())
            ->method('getDateOption');

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    function (LeadListFiltersDecoratorDelegateEvent $event) use ($contactSegmentFilterCrate, $filterDecoratorInterface): true {
                        $this->assertNotInstanceOf(FilterDecoratorInterface::class, $event->getDecorator());
                        $this->assertSame($contactSegmentFilterCrate, $event->getCrate());

                        $event->setDecorator($filterDecoratorInterface);

                        return true;
                    }
                )
            );

        $this->assertSame(
            $filterDecoratorInterface,
            $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate)
        );
    }
}
