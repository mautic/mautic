<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FileInvalidException;

class FileProperties
{
    /**
     * @param string $filename
     *
     * @throws FileInvalidException
     */
    public function getFileSize($filename): int|bool
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new FileInvalidException();
        }

        return filesize($filename);
    }
}
