<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
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
     *
     * @param TableSchemaColumnsCache $schemaCache
     * @param Container               $container
     * @param DecoratorFactory        $decoratorFactory
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
     * @param LeadList $leadList
     *
     * @return ContactSegmentFilters
     *
     * @throws \Exception
     */
    public function getSegmentFilters(LeadList $leadList)
    {
        $contactSegmentFilters = new ContactSegmentFilters();

        $filters = $leadList->getFilters();
        foreach ($filters as $filter) {
            // ContactSegmentFilterCrate is for accessing $filter as an object
            $contactSegmentFilterCrate = new ContactSegmentFilterCrate($filter);

            $decorator = $this->decoratorFactory->getDecoratorForFilter($contactSegmentFilterCrate);

            $filterQueryBuilder = $this->getQueryBuilderForFilter($decorator, $contactSegmentFilterCrate);

            $contactSegmentFilter = new ContactSegmentFilter($contactSegmentFilterCrate, $decorator, $this->schemaCache, $filterQueryBuilder);

            $contactSegmentFilters->addContactSegmentFilter($contactSegmentFilter);
        }

        return $contactSegmentFilters;
    }

    /**
     * @param FilterDecoratorInterface  $decorator
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
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
