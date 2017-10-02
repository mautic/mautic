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
use Mautic\FormBundle\Entity\Field;
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
        $uploadDir = $this->getUploadDir();

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

    /**
     * @param Submission $submission
     *
     * @todo Refactor code that result can be accessed normally and not only as a array of values
     */
    public function deleteUploadedFiles(Submission $submission)
    {
        $fields = $submission->getForm()->getFields();
        foreach ($fields as $field) {
            $this->deleteFileOfFormField($submission, $field);
        }
    }

    public function deleteFileOfFormField(Submission $submission, Field $field)
    {
        $alias   = $field->getAlias();
        $results = $submission->getResults();

        if (!$field->isFileType() || empty($results[$alias])) {
            return;
        }

        $fileName = $results[$alias];
        $this->deleteFile($fileName);
    }

    /**
     * @param string $fileName
     */
    private function deleteFile($fileName)
    {
        $uploadDir = $this->getUploadDir();

        $filePath = $uploadDir.DIRECTORY_SEPARATOR.$fileName;
        $this->fileUploader->deleteFile($filePath);
    }

    /**
     * @return string
     */
    private function getUploadDir()
    {
        return $this->coreParametersHelper->getUploadDirForForms();
    }
}
