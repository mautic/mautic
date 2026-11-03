<?php

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Mautic\LeadBundle\Segment\OperatorOptions;

trait FieldFilterDateTransformerTrait
{
    private string $context;

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * @param mixed[] $value
     */
    private function skipTransformation($value): bool
    {
        $operators = [
            OperatorOptions::BETWEEN,
            OperatorOptions::NOT_BETWEEN,
            OperatorOptions::IN_LAST,
            OperatorOptions::IN_NEXT,
        ];

        return !is_array($value) || in_array($value['operator'] ?? '', $operators);
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

        return 'segment' === $this->context
            && (!is_array($value) || in_array($value['operator'] ?? '', $operators));
    }
}
