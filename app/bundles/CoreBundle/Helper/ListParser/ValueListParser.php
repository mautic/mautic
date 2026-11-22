<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

final class ValueListParser implements ListParserInterface
{
    public function parse(mixed $list): array
    {
        if (is_array($list)) {
            throw new FormatNotSupportedException();
        }

        return [$list => $list];
    }
}
