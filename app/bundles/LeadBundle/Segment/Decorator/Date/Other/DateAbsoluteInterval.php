<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateAbsoluteInterval implements FilterDecoratorInterface
{
    private DateDecorator $dateDecorator;

    /**
     * @var string[]
     */
    private array $originalValue;

    /**
     * @param string[] $originalValue
     */
    public function __construct(
        DateDecorator $dateDecorator,
        array $originalValue,
    ) {
        $this->dateDecorator = $dateDecorator;
        $this->originalValue = $originalValue;
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
        $bounds = [];
        $dates  = [];
        foreach (['date_from' => ' 00:00:00', 'date_to' => ' 23:59:59'] as $key => $time) {
            $value = $this->originalValue[$key] ?? null;
            $date   = is_string($value) ? \DateTime::createFromFormat('M j, Y', $value) : false;
            $errors = \DateTime::getLastErrors();

            if (false === $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new \InvalidArgumentException(sprintf('Invalid "%s" value for the date range filter.', $key));
            }

            $dates[]  = $date;
            $bounds[] = $date->format('Y-m-d').$time;
        }

        if ($dates[0] > $dates[1]) {
            throw new \InvalidArgumentException('The "date_from" value must not be later than "date_to" for the date range filter.');
        }

        return $bounds;
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): string|bool
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    /**
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|string|null
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
