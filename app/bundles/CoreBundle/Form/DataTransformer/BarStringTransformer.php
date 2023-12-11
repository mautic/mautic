<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms a bar/pipe (|) separated string to and from an array.
 * Example: "Some text | separated by vertial bars" is equivalent to ['Some text', 'separated by vertial bars'].
 */
class BarStringTransformer implements DataTransformerInterface
{
    public function transform($array): string
    {
        if (!is_array($array)) {
            return '';
        }

        return implode('|', $array);
    }

    /**
     * @param mixed $string
     *
     * @return string[]
     */
    public function reverseTransform($string): array
    {
        if (!is_string($string)) {
            return [];
        }

        return array_map(
            fn (string $element): string => trim($element),
            explode('|', $string)
        );
    }
}
