<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Symfony\Component\Form\DataTransformerInterface;

class FieldFilterDefaultValueTransformer implements DataTransformerInterface
{
    private DataTransformerInterface $transformer;

    /**
     * @var array<string, string>
     */
    private array $default;

    /**
     * @param array<string, string> $default
     */
    public function __construct(DataTransformerInterface $transformer, array $default = [])
    {
        $this->transformer = $transformer;
        $this->default     = $default;
    }

    public function transform($value)
    {
        if (!is_array($value)) {
            return [];
        }

        if ($this->default) {
            foreach ($value as $key => $filter) {
                $value[$key] = array_merge($this->default, $filter);
            }
        }

        return $this->transformer->transform($value);
    }

    public function reverseTransform($value)
    {
        return $this->transformer->reverseTransform($value);
    }
}
