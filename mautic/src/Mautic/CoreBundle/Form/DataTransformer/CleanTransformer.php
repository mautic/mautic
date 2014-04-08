<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Corebundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class CleanTransformer implements DataTransformerInterface
{

    /**
     * Clean the data
     *
     * @param mixed $value
     * @return mixed|string
     */
    public function transform($value)
    {
        return trim(strip_tags($value));
    }

    /**
     * Clean the data
     *
     * @param mixed $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        return trim(strip_tags($value));;
    }
}