<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<string>|string|null, string>
 */
class ArrayStringTransformer implements DataTransformerInterface
{
    /**
     * @param array<string>|string|null $array
     *
     * @return string
     */
    public function transform($array)
    {
        if (null === $array) {
            return '';
        }
        if (is_string($array)) {
            return $array;
        }

        return implode(',', $array);
    }

    /**
     * @param string|null $string
     *
     * @return array<string>
     */
    public function reverseTransform($string)
    {
        if (!$string) {
            return [];
        }

        return array_map('trim', explode(',', $string));
    }
}
