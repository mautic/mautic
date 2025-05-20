<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateAbsoluteInterval implements FilterDecoratorInterface
{
    /**
     * @param string[] $originalValue
     */
    public function __construct(private DateDecorator $dateDecorator, private array $originalValue)
    {
    }

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate): ?string
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getOperator($contactSegmentFilterCrate);
    }

    /**
     * @param mixed[]|string $argument
     *
     * @return mixed[]|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    /**
     * @return string[]
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): array
    {
        return array_map(
            function (string $dateKey, string $stringDateHuman) {
                $date = \DateTime::createFromFormat('M j, Y', $stringDateHuman);
                $time = 'date_from' === $dateKey ? ' 00:00:00' : ' 23:59:59';

                return $date->format('Y-m-d').$time;
            },
            array_keys($this->originalValue),
            $this->originalValue
        );
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): bool|string
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): CompositeExpression|string|null
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
