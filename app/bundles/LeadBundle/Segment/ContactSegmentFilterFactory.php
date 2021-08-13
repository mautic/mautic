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
        $filterQueue     = []; // Put the filters from array into the Queue
        $lastFilter      = []; // pop the latest

        $preservedKey = ''; // preserve the key from previous iteration
        // replace filters with glue OR and operator = , with IN operator
        foreach ($filters as $filter) {
            // easy to compare
            $key = implode('_', [
                $filter['object'],
                $filter['field'],
                $filter['glue'],
                ('=' === $filter['operator']) ? 'eq' : $filter['operator'],
            ]);

            if ('or' === strtolower($filter['glue'])) {
                if (empty($filterQueue) || $preservedKey === $key) {
                    $filterQueue[] = $filter;
                } else {
                    $groupedFilter = $this->groupFilters($filterQueue);
                    if (!empty($groupedFilter)) {
                        $shrinkedFilters[] = $groupedFilter;
                    }
                    $filterQueue  = [$filter]; // reset filter queue
                }
            } else {
                if (count($filterQueue) > 0) {
                    $lastFilter = array_pop($filterQueue);
                }

                $groupedFilter = $this->groupFilters($filterQueue);
                if (!empty($groupedFilter)) {
                    $shrinkedFilters[] = $groupedFilter;
                }
                $filterQueue  = []; // reset filter queue

                if (!empty($lastFilter)) {
                    $shrinkedFilters[] = $lastFilter;
                    $lastFilter        = [];
                }

                $shrinkedFilters[] = $filter;
            }

            // preserve the key for next iteration comparison
            $preservedKey =  $key;
        }

        // add filterqueue back if not empty
        if (count($filterQueue) > 0) {
            $shrinkedFilters[] = $filterQueue[0];
        }

        return array_values($shrinkedFilters);
    }

    private function groupFilters($filterQueue)
    {
        if (empty($filterQueue)) {
            return [];
        }

        if (count($filterQueue) <= 1) {
            return $filterQueue;
        }

        $filter                         = $filterQueue[0];
        $filter['operator']             = 'in';
        $filter['properties']['filter'] = $filter['filter'] = array_map(function ($ele) {
            return $ele['filter'];
        }, $filterQueue);

        return $filter;
    }
}
