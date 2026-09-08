<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Decorator\ParseDateFilterValueTrait;

final readonly class DateAnniversary implements FilterDecoratorInterface
{
    use ParseDateFilterValueTrait;

    public function __construct(
        private DateDecorator $dateDecorator,
        private DateOptionParameters $dateOptionParameters,
    ) {
    }

    /**
     * @return string|null
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return 'like';
    }

    /**
     * @param array|string $argument
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument): string|array
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): mixed
    {
        $date           = $this->dateOptionParameters->getDefaultDate();
        $filter = $this->parseDateFilterValue(
            $contactSegmentFilterCrate->getFilter(),
            $contactSegmentFilterCrate->getOperator()
        );
        $relativeFilter = is_string($filter) ? trim(str_replace(['anniversary', 'birthday'], '', $filter)) : $filter;

        if ($relativeFilter) {
            $date->modify($relativeFilter);
        }

        return $date->toLocalString('%-m-d%');
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): string|bool
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): CompositeExpression|string|null
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
