<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class DatetimeToStringTransformer.
 */
class DatetimeToStringTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $format;

    /**
     * @param string $format
     */
    public function __construct($format = 'Y-m-d H:i')
    {
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return null;
        }

        $datetime = new \Datetime($value->format($this->format));

        return $datetime->format($this->format);
    }

    /**
     * {@inheritdoc}
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
