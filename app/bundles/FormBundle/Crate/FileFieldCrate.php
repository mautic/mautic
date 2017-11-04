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

class FileFieldCrate
{
    /**
     * @var UploadedFile
     */
    private $uploadedFile;

    /**
     * @var Field
     */
    private $field;

    public function __construct(UploadedFile $uploadedFile, Field $field)
    {
        $this->uploadedFile = $uploadedFile;
        $this->field        = $field;
    }

    /**
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }
}
