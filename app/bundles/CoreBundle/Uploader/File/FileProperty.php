<?php
/*
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Uploader\File;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class FileProperty
{
    private $path;

    /**
     * FileDecorator constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @return null|string
     */
    public function getFileMimeType()
    {
        if (null === $this->loadFile()) {
            return '';
        }

        return $this->loadFile()->getMimeType();
    }

    /**
     * @param int $decimals
     *
     * @return string
     */
    public function getFileSize($decimals  = 2)
    {
        $bytes  = $this->loadFile() ? $this->loadFile()->getSize() : 0;
        $sz     = ['B', 'K', 'M', 'G', 'TP'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$sz[$factor];
    }

    /**
     * @return null|File
     */
    private function loadFile()
    {
        $path = $this->path;
        if (!$path || !file_exists($path)) {
            return null;
        }

        try {
            $file = new File($path);
        } catch (FileNotFoundException $e) {
            $file = null;
        }

        return $file;
    }
}
