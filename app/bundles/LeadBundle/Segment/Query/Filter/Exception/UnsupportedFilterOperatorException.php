<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter\Exception;

final class UnsupportedFilterOperatorException extends \InvalidArgumentException
{
    public static function fromOperator(string $operator): self
    {
        return new self(sprintf('Unsupported filter operator "%s".', $operator));
    }
}
