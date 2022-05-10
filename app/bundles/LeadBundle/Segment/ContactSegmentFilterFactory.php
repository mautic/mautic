<?php

namespace Mautic\LeadBundle\Segment;

use Exception;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Container;

class ContactSegmentFilterFactory
{
    /**
     * @var TableSchemaColumnsCache
     */
    private $schemaCache;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var DecoratorFactory
     */
    private $decoratorFactory;

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
     * @return ContactSegmentFilters
     *
     * @throws Exception
     */
    public function getSegmentFilters(LeadList $leadList)
    {
        $contactSegmentFilters = new ContactSegmentFilters();

        $filters = $leadList->getFilters();
        foreach ($filters as $filter) {
            $contactSegmentFilters->addContactSegmentFilter($this->factorSegmentFilter($filter));
        }

        return $contactSegmentFilters;
    }

    /**
     * @param mixed[] $filter
     *
     * @return ContactSegmentFilter
     *
     * @throws \Exception
     */
    public function factorSegmentFilter(array $filter)
    {
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

        $decorator = $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

        $filterQueryBuilder = $this->getQueryBuilderForFilter($decorator, $contactSegmentFilterCrate);

        return new ContactSegmentFilter($contactSegmentFilterCrate, $decorator, $this->schemaCache, $filterQueryBuilder);
    }

    /**
     * @return FilterQueryBuilderInterface
     *
     * @throws Exception
     */
    private function getQueryBuilderForFilter(FilterDecoratorInterface $decorator, ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $qbServiceId = $decorator->getQueryType($contactSegmentFilterCrate);

        return $this->container->get($qbServiceId);
    }
}
