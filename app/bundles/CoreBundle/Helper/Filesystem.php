<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Extends Symfony's filesystem but adding the readFile method that we need to abstract for unit tests.
 * Using file_get_contents() directly makes unit testing impossible.
 *
 * @see https://github.com/symfony/filesystem/pull/4
 */
class Filesystem extends SymfonyFilesystem
{
    /**
     * Read file and return contents.
     *
     * @throws Exception\IOException
     */
    public function readFile(string $filename): string
    {
        if (false === $source = @file_get_contents($filename)) {
            throw new IOException(sprintf('Failed to read "%s" because source file could not be opened for reading.', $filename), 0, null, $filename);
        }

        return $source;
    }
}
