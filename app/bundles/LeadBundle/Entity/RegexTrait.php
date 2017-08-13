<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;


trait RegexTrait
{
    /**
     * Ensure that special characters are escaped correctly
     *
     * The end goal is to have two backslashes so in PHP, we need 8 because when the regex is used in the query
     * PHP will recognize it as 2
     *
     * @param $regex
     *
     * @return mixed
     */
    protected function prepareRegex($regex)
    {
        $search = [
            '\\\\',
            "\\",
            "\\\\'",
            "\\'",
            "'",
        ];

        $replace = [
            "\\",
            "\\\\",
            "'",
            "'",
            "\\'",
        ];

        return str_replace($search, $replace, $regex);
    }
}