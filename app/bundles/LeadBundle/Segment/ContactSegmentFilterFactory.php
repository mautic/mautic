<?php

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListMergeFiltersEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContactSegmentFilterFactory
{
    public const CUSTOM_OPERATOR = 'custom_operator';
    private $operatorsWithEmptyValuesAllowed = ['empty', '!empty', self::CUSTOM_OPERATOR];

    public function __construct(
        private TableSchemaColumnsCache $schemaCache,
        private Container $container,
        private DecoratorFactory $decoratorFactory,
        private EventDispatcherInterface $eventDispatcher
    ) {
        $this->schemaCache      = $schemaCache;
        $this->container        = $container;
        $this->decoratorFactory = $decoratorFactory;
    }

    /**
     * @param array<string, mixed> $batchLimiters
     *
     * @throws \Exception
     */
    public function getSegmentFilters(LeadList $leadList, array $batchLimiters = []): ContactSegmentFilters
    {
        $contactSegmentFilters = new ContactSegmentFilters();

        $filters = $leadList->getFilters();

        // Merge multiple filters of same field with OR
        $filters = $this->mergeFilters($filters);

        $event = new LeadListMergeFiltersEvent($filters);
        $this->eventDispatcher->dispatch(LeadEvents::LIST_FILTERS_MERGE, $event);
        $filters = $event->getFilters();

        foreach ($filters as $filter) {
            if (self::CUSTOM_OPERATOR === $filter['operator']) {
                $mergedProperty      = $filter['merged_property'];
                $factorSegmentFilter = null;
                foreach ($filter['properties'] as $index => $nestedFilter) {
                    if (!in_array($nestedFilter['operator'], $this->operatorsWithEmptyValuesAllowed) && empty($nestedFilter['filter']) && !is_numeric($nestedFilter['filter'])) {
                        continue; // If no value set for the filter, don't consider it
                    }
                    $factorSegmentFilter                    = $this->factorSegmentFilter($nestedFilter, $batchLimiters);
                    $mergedProperty[$index]['filter_value'] = $factorSegmentFilter->getParameterValue();
                }
                if ($factorSegmentFilter) {
                    $factorSegmentFilter->contactSegmentFilterCrate->setMergedProperty($mergedProperty);
                    $contactSegmentFilters->addContactSegmentFilter($factorSegmentFilter);
                }
            } else {
                if (!in_array($filter['operator'], $this->operatorsWithEmptyValuesAllowed) && empty($filter['filter']) && !is_numeric($filter['filter'])) {
                    continue; // If no value set for the filter, don't consider it
                }
                $contactSegmentFilters->addContactSegmentFilter($this->factorSegmentFilter($filter, $batchLimiters));
            }
        }

        return $contactSegmentFilters;
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $batchLimiters
     *
     * @throws \Exception
     */
    public function factorSegmentFilter(array $filter, array $batchLimiters = []): ContactSegmentFilter
    {
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $decorator = $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

        $filterQueryBuilder = $this->getQueryBuilderForFilter($decorator, $contactSegmentFilterCrate);

        return new ContactSegmentFilter($contactSegmentFilterCrate, $decorator, $this->schemaCache, $filterQueryBuilder, $batchLimiters);
    }

    /**
     * @return FilterQueryBuilderInterface
     *
     * @throws \Exception
     */
    private function getQueryBuilderForFilter(FilterDecoratorInterface $decorator, ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $qbServiceId = $decorator->getQueryType($contactSegmentFilterCrate);

        return $this->container->get($qbServiceId);
    }
}
