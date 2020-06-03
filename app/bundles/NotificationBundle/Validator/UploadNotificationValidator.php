<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Mautic\FormBundle\Exception\FileValidationException;
use Mautic\FormBundle\Exception\NoFileGivenException;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Form\Type\NotificationType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class UploadNotificationValidator
{
    /**
     * @var FileUploadValidator
     */
    private $fileUploadValidator;

    public function __construct(FileUploadValidator $fileUploadValidator)
    {
        $this->fileUploadValidator = $fileUploadValidator;
    }

    /**
     * @param Notification $notification
     * @param Request      $request
     *
     * @return UploadedFile
     *
     * @throws FileValidationException
     * @throws NoFileGivenException
     */
    public function processFileValidation(Notification $notification, Request $request)
    {
        $files = $request->files->get('notification');

        if (!$files || !array_key_exists($notification->getFileAlias(), $files)) {
            throw new NoFileGivenException();
        }

        /** @var UploadedFile $file */
        $file = $files[$notification->getFileAlias()];

        if (!$file instanceof UploadedFile) {
            throw new NoFileGivenException();
        }

        $allowedExtensions = NotificationType::PROPERTY_ALLOWED_FILE_EXTENSIONS;

        try {
            $this->fileUploadValidator->validate($file->getSize(), $file->getClientOriginalExtension(), null, $allowedExtensions, 'mautic.form.submission.error.file.extension', 'mautic.form.submission.error.file.size');

            return $file;
        } catch (FileInvalidException $e) {
            throw new FileValidationException($e->getMessage());
        }
    }
}
