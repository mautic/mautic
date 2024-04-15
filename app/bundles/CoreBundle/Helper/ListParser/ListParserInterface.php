<?php

namespace Mautic\CoreBundle\Helper\ListParser;

use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;

interface ListParserInterface
{
    /**
     * @param mixed $list
     *
     * @throws FormatNotSupportedException
     */
    public function parse($list): array;
}
