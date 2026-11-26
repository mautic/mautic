<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

final class JsonListParser implements ListParserInterface
{
    public function parse(array|string|int|float|bool|null $list): array
    {
        if (!is_string($list)) {
            throw new FormatNotSupportedException();
        }

        $parsedList = json_decode($list, true);
        if (!is_array($parsedList)) {
            throw new FormatNotSupportedException();
        }

        return $parsedList;
    }
}
