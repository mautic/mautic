<?php

namespace Mautic\CoreBundle\Helper\RandomHelper;

/**
 * Interface RandomHelperIdentifier.
 */
interface RandomHelperInterface
{
    /**
     * Generate random string.
     *
     * @param int    $length
     * @param string $charlist
     *
     * @return string
     */
    public function generate($length = 10, $charlist = '0-9a-z');
}
