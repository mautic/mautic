<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent;
use Mautic\LeadBundle\Exception\FilterNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DecoratorFactory
{
    /**
     * @var ContactSegmentFilterDictionary
     */
    private $contactSegmentFilterDictionary;

    /**
     * @var BaseDecorator
     */
    private $baseDecorator;

    /**
     * @var CustomMappedDecorator
     */
    private $customMappedDecorator;

    /**
     * @var CompanyDecorator
     */
    private $companyDecorator;

    /**
     * @var DateOptionFactory
     */
    private $dateOptionFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
        BaseDecorator $baseDecorator,
        CustomMappedDecorator $customMappedDecorator,
        DateOptionFactory $dateOptionFactory,
        CompanyDecorator $companyDecorator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->baseDecorator                  = $baseDecorator;
        $this->customMappedDecorator          = $customMappedDecorator;
        $this->dateOptionFactory              = $dateOptionFactory;
        $this->contactSegmentFilterDictionary = $contactSegmentFilterDictionary;
        $this->companyDecorator               = $companyDecorator;
        $this->eventDispatcher                = $eventDispatcher;
    }

    /**
     * @return FilterDecoratorInterface
     */
    public function getDecoratorForFilter(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $decoratorEvent = new LeadListFiltersDecoratorDelegateEvent($contactSegmentFilterCrate);

        $this->eventDispatcher->dispatch(LeadEvents::SEGMENT_ON_DECORATOR_DELEGATE, $decoratorEvent);
        if ($decorator = $decoratorEvent->getDecorator()) {
            return $decorator;
        }

        if ($contactSegmentFilterCrate->isDateType()) {
            $dateDecorator = $this->dateOptionFactory->getDateOption($contactSegmentFilterCrate);

            if ($contactSegmentFilterCrate->isCompanyType()) {
                return new DateCompanyDecorator($dateDecorator);
            }

            return $dateDecorator;
        }

        $originalField = $contactSegmentFilterCrate->getField();

        try {
            $this->contactSegmentFilterDictionary->getFilter($originalField);

            return $this->customMappedDecorator;
        } catch (FilterNotFoundException $e) {
            if ($contactSegmentFilterCrate->isCompanyType()) {
                return $this->companyDecorator;
            }

            return $this->baseDecorator;
        }
    }
}
