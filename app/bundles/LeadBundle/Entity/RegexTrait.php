<?php

namespace Mautic\LeadBundle\Entity;

trait RegexTrait
{
    /**
     * Ensure that special characters are escaped correctly.
     *
     * @return array|string|string[]
     */
    protected function prepareRegex($regex)
    {
        $search = [
            '\\\\',
        ];

        $replace = [
            '\\',
        ];

        return str_replace($search, $replace, $regex);
    }
}
