<?php

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

class BarListParser implements ListParserInterface
{
    public function parse($list): array
    {
        if (!is_string($list)) {
            throw new FormatNotSupportedException();
        }

        if (!str_contains($list, '|')) {
            throw new FormatNotSupportedException();
        }

        // label/value pairs
        $parts = explode('||', $list);
        if (count($parts) > 1) {
            $labels = explode('|', $parts[0]);
            $values = explode('|', $parts[1]);

            return array_combine($values, $labels);
        }

        // label and values are the same
        $labels = explode('|', $list);
        $values = $labels;

        return array_combine($values, $labels);
    }
}
