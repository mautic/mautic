<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Symfony\Component\Form\DataTransformerInterface;

class FieldFilterDateTransformer implements DataTransformerInterface
{
    use FieldFilterDateTransformerTrait;

    public function transform($value)
    {
        if ($this->skipTransformation($value)) {
            return $value;
        }

        $bcFilter    = $value['filter'] ?? '';
        $filterVal   = $value['properties']['filter'] ?? $bcFilter;

        if (!is_array($filterVal)) {
            $value['properties']['filter'] = ['absoluteDate' => $filterVal];
        }

        return $value;
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}
