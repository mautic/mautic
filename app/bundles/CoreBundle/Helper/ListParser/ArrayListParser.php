<?php

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

class ArrayListParser implements ListParserInterface
{
    public function parse($list): array
    {
        if (!is_array($list)) {
            throw new FormatNotSupportedException();
        }

        if (!array_filter(array_keys($list), 'is_string') && !array_filter($list, 'is_array')) {
            $choices = [];

            // Numerical array so set labels as values and return as choices
            foreach ($list as $value) {
                if ('' === $value || null === $value) {
                    continue;
                }

                $choices[trim(html_entity_decode($value, ENT_QUOTES))] = trim(html_entity_decode($value, ENT_QUOTES));
            }

            return $choices;
        }

        return $list;
    }
}
