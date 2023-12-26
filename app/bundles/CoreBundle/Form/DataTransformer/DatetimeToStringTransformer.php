<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string|null, \DateTime>
 */
class DatetimeToStringTransformer implements DataTransformerInterface
{
    /**
     * @param string $format
     */
    public function __construct(
        private $format = 'Y-m-d H:i'
    ) {
    }

    /**
     * @param \DateTime|null $value
     *
     * @return string
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return null;
        }

        $datetime = new \DateTime($value->format($this->format));

        return $datetime->format($this->format);
    }

    /**
     * @param string|null $value
     *
     * @return \DateTime
     */
    public function transform($value)
    {
        if (empty($value)) {
            return null;
        }

        return \DateTime::createFromFormat(
            $this->format,
            $value
        );
    }
}
