<?php

namespace Mautic\CoreBundle\Helper\HashHelper;

final class HashHelper implements HashHelperInterface
{
    /**
     * @param string $algo
     * @param string $data
     * @param bool   $raw_output
     */
    public function hash($algo, $data, $raw_output = false): string
    {
        return hash($algo, $data, $raw_output);
    }
}
