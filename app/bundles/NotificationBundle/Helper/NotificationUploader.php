<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Helper;

use GuzzleHttp\Psr7\UploadedFile;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Model\NotificationModel;

class NotificationUploader
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
     * @param Notification $notification
     * @param $request
     * @param FileUploader      $fileUploader
     * @param NotificationModel $notificationModel
     */
    public function uploadFiles(Notification $notification, $request, FileUploader $fileUploader, NotificationModel $notificationModel)
    {
        $files         = $request->files->all();
        $uploadedFiles = [];
        foreach ($notificationModel->getUploadFilesName() as $fileName) {
            /* @var UploadedFile $file */
            if (empty($files[$fileName])) {
                continue;
            }
            $file = $files[$fileName];
            try {
                $uploadDir    = $this->getUploadDir($notification);
                $uploadedFile = $fileUploader->upload($uploadDir, $file);
                $var          = 'set'.ucfirst($fileName);
                $notification->$var($uploadedFile);
                $uploadedFiles[$fileName] = $uploadDir.DIRECTORY_SEPARATOR.$uploadedFile;
            } catch (FileUploadException $e) {
                foreach ($uploadedFiles as $filePath) {
                    $fileUploader->delete($filePath);
                }
            }
        }
    }

    /**
     * @param Notification $notification
     * @param string       $fileName
     *
     * @return string
     */
    public function getCompleteFilePath(Notification $notification, $fileName)
    {
        $uploadDir = $this->getUploadDir($notification);

        return $uploadDir.DIRECTORY_SEPARATOR.$fileName;
    }

    /**
     * @param Notification $notification
     */
    public function deleteAllFilesOfNotification(Notification $notification)
    {
        $uploadDir = $this->getUploadDir($notification);
        $this->fileUploader->delete($uploadDir);
    }

    /**
     * @param Notification $notification
     *
     * @return string
     */
    private function getUploadDir(Notification $notification)
    {
        $notificationId        = $notification->getId();
        $notificationUploadDir = $this->getUploadDirOfNotification($notification);

        return $notificationUploadDir.DIRECTORY_SEPARATOR.$notificationId;
    }
}
