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
    public function transform(mixed $array): mixed
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
    public function reverseTransform(mixed $string): mixed
    {
        if (!$string) {
            return [];
        }

        return array_map('trim', explode("\n", $string));
    }
}
