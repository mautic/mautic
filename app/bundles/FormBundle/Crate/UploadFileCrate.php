<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Crate;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileCrate
{
    /**
     * @var array|UploadedFile[]
     */
    private $files;

    public function __construct()
    {
        $this->files = [];
    }

    /**
     * @return array|UploadedFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function addFile(UploadedFile $file, $alias)
    {
        $this->files[$alias] = $file;
    }

    /**
     * @return bool
     */
    public function hasFiles()
    {
        return !empty($this->files);
    }
}
