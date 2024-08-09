<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<string>|null, string|null>
 */
class ArrayLinebreakTransformer implements DataTransformerInterface
{
    /**
     * @param array<string>|null $array
     *
     * @return string
     */
    public function transform($array)
    {
        if (null === $array) {
            return '';
        }

        return implode("\n", $array);
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

        return array_map('trim', explode("\n", $string));
    }
}
