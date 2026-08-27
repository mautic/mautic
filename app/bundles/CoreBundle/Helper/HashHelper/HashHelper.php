<?php

namespace Mautic\CoreBundle\Helper\HashHelper;

final class HashHelper implements HashHelperInterface
{
    /**
     * @param string $algo
     * @param string $data
     */
    public function hash($algo, $data, bool $raw_output = false): string
    {
        return hash($algo, $data, $raw_output);
    }
}
