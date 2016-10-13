<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NullToEmptyTransformer implements DataTransformerInterface
{
    /**
     * Does not transform anything.
     *
     * @param string|null $value
     *
     * @return string
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * Transforms a null to an empty string.
     *
     * @param string $value
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
