<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
