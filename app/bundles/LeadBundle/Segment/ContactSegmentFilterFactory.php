<?php

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Container;

class ContactSegmentFilterFactory
{
    private \Mautic\LeadBundle\Segment\TableSchemaColumnsCache $schemaCache;

    private \Symfony\Component\DependencyInjection\Container $container;

    private \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory $decoratorFactory;

    public function __construct(
        TableSchemaColumnsCache $schemaCache,
        Container $container,
        DecoratorFactory $decoratorFactory
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
        foreach ($filters as $filter) {
            $contactSegmentFilters->addContactSegmentFilter($this->factorSegmentFilter($filter, $batchLimiters));
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
