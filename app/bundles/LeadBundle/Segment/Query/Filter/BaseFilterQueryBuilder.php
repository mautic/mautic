<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BaseFilterQueryBuilder implements FilterQueryBuilderInterface
{
    /**
     * @var RandomParameterName
     */
    private $parameterNameGenerator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher
    ) {
        $this->parameterNameGenerator = $randomParameterNameService;
        $this->dispatcher             = $dispatcher;
    }

    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.basic';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        // Check if the column exists in the table
        $filter->getColumn();

        $filterParameters = $filter->getParameterValue();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $event = new SegmentOperatorQueryBuilderEvent($queryBuilder, $filter, $filter->getParameterHolder($parameters));
        $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_OPERATOR_QUERYBUILDER_ON_GENERATE, $event);

        if (!$event->wasOperatorHandled()) {
            throw new \Exception('Dunno how to handle operator "'.$filter->getOperator().'"');
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    /**
     * @param RandomParameterName $parameterNameGenerator
     *
     * @return BaseFilterQueryBuilder
     */
    public function setParameterNameGenerator($parameterNameGenerator)
    {
        $this->parameterNameGenerator = $parameterNameGenerator;

        return $this;
    }

    /**
     * @return string
     */
    protected function generateRandomParameterName()
    {
        return $this->parameterNameGenerator->generateRandomParameterName();
    }
}
