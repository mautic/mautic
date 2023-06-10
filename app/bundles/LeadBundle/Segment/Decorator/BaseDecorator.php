<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Entity\RegexTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;

/**
 * Class BaseDecorator.
 */
class BaseDecorator implements FilterDecoratorInterface
{
    use RegexTrait;

    /**
     * @var ContactSegmentFilterOperator
     */
    protected $contactSegmentFilterOperator;

    /**
     * BaseDecorator constructor.
     */
    public function __construct(
        ContactSegmentFilterOperator $contactSegmentFilterOperator
    ) {
        $this->contactSegmentFilterOperator = $contactSegmentFilterOperator;
    }

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate): ?string
    {
        return $contactSegmentFilterCrate->getField();
    }

    /**
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isContactType()) {
            return MAUTIC_TABLE_PREFIX.'leads';
        }

        return MAUTIC_TABLE_PREFIX.'companies';
    }

    /**
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $operator = $this->contactSegmentFilterOperator->fixOperator($contactSegmentFilterCrate->getOperator());

        return match ($operator) {
            'startsWith', 'endsWith', 'contains' => 'like',
            default => $operator,
        };
    }

    /**
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return BaseFilterQueryBuilder::getServiceId();
    }

    /**
     * @param array|string $argument
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument): array|string
    {
        if (is_array($argument)) {
            $result = [];
            foreach ($argument as $arg) {
                $result[] = $this->getParameterHolder($contactSegmentFilterCrate, $arg);
            }

            return $result;
        }

        return ':'.$argument;
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): array|bool|float|string|null
    {
        $filter = $contactSegmentFilterCrate->getFilter();

        if ($contactSegmentFilterCrate->filterValueDoNotNeedAdjustment()) {
            return $filter;
        }

        switch ($contactSegmentFilterCrate->getOperator()) {
            case 'in':
            case '!in':
                return !is_array($filter) ? explode('|', $filter) : $filter;
            case 'like':
            case '!like':
                return !str_contains($filter, '%') ? '%'.$filter.'%' : $filter;
            case 'contains':
                return '%'.$filter.'%';
            case 'startsWith':
                return $filter.'%';
            case 'endsWith':
                return '%'.$filter;
            case 'regexp':
            case '!regexp':
                return $this->prepareRegex($filter);
            case 'multiselect':
            case '!multiselect':
                $filter = (array) $filter;

                foreach ($filter as $key => $value) {
                    $filter[$key] = sprintf('(([|]|^)%s([|]|$))', preg_quote($value, '/'));
                }

                return $filter;
        }

        return $filter;
    }

    /**
     * @return bool
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return false;
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): CompositeExpression|string|null
    {
        return null;
    }
}
