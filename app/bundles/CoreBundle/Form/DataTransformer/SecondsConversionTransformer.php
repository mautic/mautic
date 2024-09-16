<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class SecondsConversionTransformer.
 */
class SecondsConversionTransformer implements DataTransformerInterface
{
    private $viewFormat;

    public function __construct($viewFormat = 'H')
    {
        $this->viewFormat = $viewFormat;
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

        switch ($this->viewFormat) {
            case 'i':
                $value *= 60;
                break;
            case 'H':
                $value *= 3600;
                break;
            case 'd':
                $value *= 86400;
                break;
            case 'm':
                $value *= 2592000;
                break;
        }

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

        switch ($this->viewFormat) {
            case 'i':
                $value /= 60;
                break;
            case 'H':
                $value /= 3600;
                break;
            case 'd':
                $value /= 86400;
                break;
            case 'm':
                $value /= 2592000;
                break;
        }

        return $value;
    }
}
