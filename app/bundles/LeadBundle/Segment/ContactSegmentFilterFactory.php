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
        $arrStacks       = []; // Put the same filters from array into Stacks

        $previousKey = ''; // preserve the key from previous iteration
        // replace filters with glue OR and operator = , with IN operator
        foreach ($filters as $filter) {
            // treat the first filter glue as 'or'
            if (empty($previousKey)) {
                $filter['glue'] = 'or';
            }

            // easy to compare
            $key = implode('_', [
                $filter['object'],
                $filter['field'],
                $filter['glue'],
                $filter['operator'],
            ]);

            if ('or' === strtolower($filter['glue']) && '=' === $filter['operator']) {
                if (!isset($arrStacks[$key])) {
                    $arrStacks[$key] = [];
                }

                array_push($arrStacks[$key], $filter);
            } else { // glue = and
                // if 'or' followed by 'and', it becomes - or (cond1 and cond2)
                if (isset($arrStacks[$previousKey]) && count($arrStacks[$previousKey]) > 0) {
                    $previousFilter = array_pop($arrStacks[$previousKey]);
                    array_push($shrinkedFilters, $previousFilter);
                }

                array_push($shrinkedFilters, $filter);
            }

            $previousKey = $key;
        }

        // add all grouped conditions back
        foreach ($arrStacks as $stack) {
            $groupedFilter = $this->groupFilters($stack);
            if (!empty($groupedFilter)) {
                $shrinkedFilters[] = $groupedFilter;
            }
        }

        return array_values($shrinkedFilters);
    }

    private function groupFilters($stack)
    {
        if (empty($stack)) {
            return [];
        }

        if (count($stack) <= 1) {
            return $stack[0];
        }

        $filter                         = $stack[0];
        $filter['operator']             = 'in';
        $filter['properties']['filter'] = $filter['filter'] = array_map(function ($ele) {
            return $ele['filter'];
        }, $stack);

        return $filter;
    }
}
