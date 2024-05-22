<?php

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Mautic\LeadBundle\Segment\OperatorOptions;

trait FieldFilterDateTransformerTrait
{
    /**
     * @param mixed[] $value
     */
    private function skipTransformation($value): bool
    {
        return !is_array($value) || (in_array($value['operator'] ?? '', [OperatorOptions::BETWEEN, OperatorOptions::NOT_BETWEEN]));
    }

    /**
     * @param mixed[] $value
     */
    private function isAbsoluteRelativeDateFilterAllowed($value): bool
    {
        $operators = [
            OperatorOptions::GREATER_THAN,
            OperatorOptions::LESS_THAN,
            OperatorOptions::GREATER_THAN_OR_EQUAL,
            OperatorOptions::LESS_THAN_OR_EQUAL,
        ];

        return !is_array($value) || in_array($value['operator'] ?? '', $operators);
    }
}
