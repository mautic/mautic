<?php

/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FileInvalidException;

class FileProperties
{
    /**
     * @param string $filename
     *
     * @return int
     *
     * @throws FileInvalidException
     */
    public function getFileSize($filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new FileInvalidException();
        }

        return filesize($filename);
    }
}
