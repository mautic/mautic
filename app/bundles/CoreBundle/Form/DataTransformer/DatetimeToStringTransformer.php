<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

        $val = \DateTime::createFromFormat(
            $this->format,
            $value
        );

        return $val;
    }
}
