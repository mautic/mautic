<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class SecondsConversionTransformer implements DataTransformerInterface
{
    public function __construct(
        private $viewFormat = 'H'
    ) {
    }

    /**
     * Converts to format.
     *
     * @param string|null $value
     *
     * @return string
     */
    public function reverseTransform($value)
    {
        $value = (int) $value;

        match ($this->viewFormat) {
            'i'     => $value *= 60,
            'H'     => $value *= 3600,
            'd'     => $value *= 86400,
            'm'     => $value *= 2_592_000,
            default => $value,
        };

        return $value;
    }

    /**
     * Converts to seconds.
     *
     * @param string $value
     *
     * @return string
     */
    public function transform($value)
    {
        $value = (int) $value;

        match ($this->viewFormat) {
            'i'     => $value /= 60,
            'H'     => $value /= 3600,
            'd'     => $value /= 86400,
            'm'     => $value /= 2_592_000,
            default => $value,
        };

        return $value;
    }
}
