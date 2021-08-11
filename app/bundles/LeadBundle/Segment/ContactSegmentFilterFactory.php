<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

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

    /**
     * ContactSegmentFilterFactory constructor.
     */
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
     * @throws \Exception
     */
    public function getSegmentFilters(LeadList $leadList)
    {
        $contactSegmentFilters = new ContactSegmentFilters();

        $filters = $leadList->getFilters();

        // Merge multiple filters of same field with OR
        $filters = $this->mergeFilters($filters);

        foreach ($filters as $filter) {
            $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

            $decorator = $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

            $filterQueryBuilder = $this->getQueryBuilderForFilter($decorator, $contactSegmentFilterCrate);

            $contactSegmentFilter = new ContactSegmentFilter($contactSegmentFilterCrate, $decorator, $this->schemaCache, $filterQueryBuilder);

            $contactSegmentFilters->addContactSegmentFilter($contactSegmentFilter);
        }

        return $contactSegmentFilters;
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

    /**
     * @param $filters
     *
     * @return array
     */
    private function mergeFilters($filters)
    {
        $shrinkedFilters = [];
        // replace condition OR on the same field with '=' filter, with IN operator
        foreach ($filters as $filter) {
            $key = implode('_', [
                $filter['object'],
                $filter['field'],
                $filter['glue'],
                ('=' === $filter['operator']) ? 'eq' : $filter['operator'],
            ]);

            if (isset($shrinkedFilters[$key])) {
                $shrinkedFilters[$key]['operator']             = 'in'; // changes = to in
                $shrinkedFilters[$key]['properties']['filter'] = array_merge(
                    (array) $shrinkedFilters[$key]['properties']['filter'],
                    (array) $filter['filter']
                );
                $shrinkedFilters[$key]['filter'] = array_merge(
                    (array) $shrinkedFilters[$key]['filter'],
                    (array) $filter['filter']
                );
            } else {
                $shrinkedFilters[$key] = $filter;
            }
        }

        return array_values($shrinkedFilters);
    }
}
