<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\FileInvalidException;

final class FileProperties
{
    /**
     * @throws FileInvalidException
     */
    public function getFileSize(string $filename): int|bool
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new FileInvalidException();
        }

        return filesize($filename);
    }
}
