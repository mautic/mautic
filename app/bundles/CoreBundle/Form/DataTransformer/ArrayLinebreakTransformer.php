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

/**
 * Class ArrayLinebreakTransformer.
 */
class ArrayLinebreakTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function transform($array)
    {
        if (null === $array) {
            return '';
        }

        return implode("\n", $array);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($string)
    {
        if (!$string) {
            return [];
        }

        return array_map('trim', explode("\n", $string));
    }
}
