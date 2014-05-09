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

class ArrayStringTransformer implements DataTransformerInterface
{

    /**
     * Transforms an array to a string
     *
     * @param  array|null $array
     * @return string
     */
    public function transform($array)
    {
        if ($array === null) {
            return "";
        }

        return implode(",", $array);
    }

    /**
     * Transforms a string to an array
     *
     * @param  string $string
     *
     * @return array|null
     */
    public function reverseTransform($string)
    {
        if (!$string) {
            return array();
        }

        return array_map('trim', explode(',', $string));
    }
}