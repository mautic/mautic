<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Submission;

class FormUploader
{
    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(FileUploader $fileUploader, CoreParametersHelper $coreParametersHelper)
    {
        $this->fileUploader         = $fileUploader;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param UploadFileCrate $filesToUpload
     * @param Submission      $submission
     *
     * @throws FileUploadException
     */
    public function uploadFiles(UploadFileCrate $filesToUpload, Submission $submission)
    {
        $uploadedFiles = [];
        if (!$filesToUpload->hasFiles()) {
            return;
        }

        $result    = $submission->getResults();
        $files     = $filesToUpload->getFiles();
        $uploadDir = $this->coreParametersHelper->getUploadDirForForms();

        $alias = ''; //Only for IDE - will be overriden by foreach

        try {
            foreach ($files as $alias => $file) {
                $fileName        = $this->fileUploader->upload($uploadDir, $file);
                $result[$alias]  = $fileName;
                $uploadedFiles[] = $uploadDir.DIRECTORY_SEPARATOR.$fileName;
            }
            $submission->setResults($result);
        } catch (FileUploadException $e) {
            foreach ($uploadedFiles as $filePath) {
                $this->fileUploader->deleteFile($filePath);
            }
            throw new FileUploadException($alias);
        }
    }
}
