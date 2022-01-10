<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms a bar/pipe (|) separated string to and from an array.
 * Example: "Some text | separated by vertial bars" is equivalent to ['Some text', 'separated by vertial bars'].
 */
class BarStringTransformer implements DataTransformerInterface
{
    public function transform($array): string
    {
        if (!is_array($array)) {
            return '';
        }

        return implode('|', $array);
    }

    /**
     * @param mixed $string
     *
     * @return string[]
     */
    public function reverseTransform($string): array
    {
        if (!is_string($string)) {
            return [];
        }

        return array_map(
            function (string $element) {
                return trim($element);
            },
            explode('|', $string)
        );
    }
}
