<?php

namespace Mautic\LeadBundle\Entity;

trait RegexTrait
{
    /**
     * Ensure that special characters are escaped correctly.
     *
     * @return string|string[]
     */
    protected function prepareRegex($regex): string|array
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
