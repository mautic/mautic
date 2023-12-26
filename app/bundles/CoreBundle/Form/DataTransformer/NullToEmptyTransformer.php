<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string|null, string>
 */
class NullToEmptyTransformer implements DataTransformerInterface
{
    /**
     * Does not transform anything.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * Transforms a null to an empty string.
     *
     * @param string|null $value
     *
     * @return string
     */
    public function reverseTransform($value)
    {
        if (is_null($value)) {
            return '';
        }

        return $value;
    }
}
