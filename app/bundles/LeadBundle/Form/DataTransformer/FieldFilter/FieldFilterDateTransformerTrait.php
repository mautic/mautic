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
}
