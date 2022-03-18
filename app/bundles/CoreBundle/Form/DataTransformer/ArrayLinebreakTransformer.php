<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ArrayLinebreakTransformer.
 */
class ArrayLinebreakTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($string)
    {
        if (!$string) {
            return [];
        }

        return array_map('trim', explode("\n", $string));
    }
}
