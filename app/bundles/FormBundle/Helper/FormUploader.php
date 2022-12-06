<?php

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
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
     * @throws FileUploadException
     */
    public function uploadFiles(UploadFileCrate $filesToUpload, Submission $submission)
    {
        $uploadedFiles = [];
        $result        = $submission->getResults();
        $alias         = ''; //Only for IDE - will be overriden by foreach

        try {
            foreach ($filesToUpload as $fileFieldCrate) {
                $field           = $fileFieldCrate->getField();
                $alias           = $field->getAlias();
                $uploadDir       = $this->getUploadDir($field);
                $fileName        = $this->fileUploader->upload($uploadDir, $fileFieldCrate->getUploadedFile());
                $result[$alias]  = $fileName;
                $uploadedFile    = $uploadDir.DIRECTORY_SEPARATOR.$fileName;
                $this->fixRotationJPG($uploadedFile);
                $uploadedFiles[] =$uploadedFile;
            }
            $submission->setResults($result);
        } catch (FileUploadException $e) {
            foreach ($uploadedFiles as $filePath) {
                $this->fileUploader->delete($filePath);
            }
            throw new FileUploadException($alias);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getCompleteFilePath(Field $field, $fileName)
    {
        $uploadDir = $this->getUploadDir($field);

        return $uploadDir.DIRECTORY_SEPARATOR.$fileName;
    }

    public function deleteAllFilesOfFormField(Field $field)
    {
        if (!$field->isFileType()) {
            return;
        }

        $uploadDir = $this->getUploadDir($field);
        $this->fileUploader->delete($uploadDir);
    }

    public function deleteFilesOfForm(Form $form)
    {
        $formId        = $form->getId() ?: $form->deletedId;
        $formUploadDir = $this->getUploadDirOfForm($formId);
        $this->fileUploader->delete($formUploadDir);
    }

    /**
     * @todo Refactor code that result can be accessed normally and not only as a array of values
     */
    public function deleteUploadedFiles(Submission $submission)
    {
        $fields = $submission->getForm()->getFields();
        foreach ($fields as $field) {
            $this->deleteFileOfFormField($submission, $field);
        }
    }

    private function deleteFileOfFormField(Submission $submission, Field $field)
    {
        $alias   = $field->getAlias();
        $results = $submission->getResults();

        if (!$field->isFileType() || empty($results[$alias])) {
            return;
        }

        $fileName = $results[$alias];
        $filePath = $this->getCompleteFilePath($field, $fileName);
        $this->fileUploader->delete($filePath);
    }

    /**
     * @return string
     */
    private function getUploadDir(Field $field)
    {
        $fieldId       = $field->getId();
        $formUploadDir = $this->getUploadDirOfForm($field->getForm()->getId());

        return $formUploadDir.DIRECTORY_SEPARATOR.$fieldId;
    }

    /**
     * @return string
     *
     * @throws \LogicException If formId is null
     */
    private function getUploadDirOfForm(int $formId)
    {
        $uploadDir = $this->coreParametersHelper->get('form_upload_dir');

        return $uploadDir.DIRECTORY_SEPARATOR.$formId;
    }

    /**
     * Fix iOS picture orientation after upload PHP
     * https://stackoverflow.com/questions/22308921/fix-ios-picture-orientation-after-upload-php.
     *
     * @param $filename
     */
    private function fixRotationJPG($filename)
    {
        if (IMAGETYPE_JPEG != exif_imagetype($filename)) {
            return;
        }
        $exif = exif_read_data($filename);
        if (empty($exif['Orientation'])) {
            return;
        }
        $ort  = $exif['Orientation']; /*STORES ORIENTATION FROM IMAGE */
        $ort1 = $ort;
        $exif = exif_read_data($filename, '', true);
        if (!empty($ort1)) {
            $image = imagecreatefromjpeg($filename);
            $ort   = $ort1;
            switch ($ort) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;

                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
        imagejpeg($image, $filename, 90);
    }
}
