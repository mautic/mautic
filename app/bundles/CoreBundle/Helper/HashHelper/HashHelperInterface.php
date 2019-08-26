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
 * Interface HashHelperInterface.
 */
interface HashHelperInterface
{
    /**
     * Generate a hash value.
     *
     * @param string $algo       Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
     * @param string $data       Message to be hashed
     * @param bool   $raw_output When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
     *
     * @return string a string containing the calculated message digest as lowercase hexits
     *                unless <i>raw_output</i> is set to true in which case the raw
     *                binary representation of the message digest is returned
     */
    public function hash($algo, $data, $raw_output = false);
}
