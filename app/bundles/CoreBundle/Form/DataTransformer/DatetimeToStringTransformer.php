<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DatetimeToStringTransformer implements DataTransformerInterface
{

    private $format;

    public function __construct($format = 'Y-m-d H:i')
    {
        $this->format = $format;
    }

    /**
     * Transforms a DateTime object to a string
     *
     * @param  array|null $array
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
     * Transforms a string to a DateTime object
     *
     * @param  string $value
     *
     * @return DateTime|null
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