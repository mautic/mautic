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

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileCrate implements \Iterator
{
    /**
     * @var array|FileFieldCrate[]
     */
    private $fileFieldCrate = [];

    /**
     * @var int
     */
    private $position = 0;

    public function addFile(UploadedFile $file, Field $field)
    {
        $this->fileFieldCrate[] = new FileFieldCrate($file, $field);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->fileFieldCrate[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->fileFieldCrate[$this->position]);
    }
}
