<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ArrayStringTransformer implements DataTransformerInterface
{
    /**
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
     * @return array
     */
    public function reverseTransform($string)
    {
        if (!$string) {
            return [];
        }

        return array_map('trim', explode(',', $string));
    }
}
