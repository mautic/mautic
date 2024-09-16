<?php

namespace Mautic\CoreBundle\Helper\HashHelper;

/**
 * Class HashHelper.
 */
final class HashHelper implements HashHelperInterface
{
    /**
     * @param string $algo
     * @param string $data
     * @param bool   $raw_output
     *
     * @return string
     */
    public function hash($algo, $data, $raw_output = false)
    {
        return hash($algo, $data, $raw_output);
    }
}
