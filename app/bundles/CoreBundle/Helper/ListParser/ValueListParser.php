<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

class ValueListParser implements ListParserInterface
{
    public function parse($list): array
    {
        if (is_array($list)) {
            throw new FormatNotSupportedException();
        }

        return [$list => $list];
    }
}
