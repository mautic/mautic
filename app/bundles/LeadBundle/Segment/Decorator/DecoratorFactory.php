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
    public function __construct(
        private ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
        private BaseDecorator $baseDecorator,
        private CustomMappedDecorator $customMappedDecorator,
        private DateOptionFactory $dateOptionFactory,
        private CompanyDecorator $companyDecorator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return FilterDecoratorInterface
     */
    public function getDecoratorForFilter(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $decoratorEvent = new LeadListFiltersDecoratorDelegateEvent($contactSegmentFilterCrate);

        $this->eventDispatcher->dispatch($decoratorEvent, LeadEvents::SEGMENT_ON_DECORATOR_DELEGATE);
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
        } catch (FilterNotFoundException) {
            if ($contactSegmentFilterCrate->isCompanyType()) {
                return $this->companyDecorator;
            }

            return $this->baseDecorator;
        }
    }
}
