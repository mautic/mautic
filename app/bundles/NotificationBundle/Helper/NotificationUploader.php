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
use Symfony\Component\Form\Form;

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
    public function uploadFiles(Notification $notification, $request, FileUploader $fileUploader, NotificationModel $notificationModel, Form $form)
    {
        $files         = $request->files->all()['notification'];

        $uploadedFiles = [];
        $deteledFiles  = [];
        foreach ($notificationModel->getUploadFilesName() as $fileName) {
            $getVar          = 'get'.ucfirst($fileName);
            $setVar          = 'set'.ucfirst($fileName);

            $uploadDir    = $this->getUploadDir($notification);

            // Delete file
            if (!empty($form->get($fileName.'_delete')->getData())) {
                $fileUploader->delete($uploadDir.DIRECTORY_SEPARATOR.$notification->$getVar());
                $notification->$setVar('');
            }

            /* @var UploadedFile $file */
            if (empty($files[$fileName])) {
                continue;
            }
            $file = $files[$fileName];

            try {
                $uploadedFile = $fileUploader->upload($uploadDir, $file);
                $notification->$setVar($uploadedFile);
            } catch (FileUploadException $e) {
                $fileUploader->delete($uploadDir.DIRECTORY_SEPARATOR.$uploadedFile);
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
     * @param $notification
     *
     * @return string
     */
    public function getFullUrl($notification, $fileName)
    {
        return $this->coreParametersHelper->getParameter('site_url').DIRECTORY_SEPARATOR.$this->coreParametersHelper->getParameter('notification_upload_dir').DIRECTORY_SEPARATOR.$notification->getId().DIRECTORY_SEPARATOR.$fileName;
    }

    /**
     * @param Notification $notification
     *
     * @return string
     */
    private function getUploadDir(Notification $notification)
    {
        return $this->coreParametersHelper->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$this->coreParametersHelper->getParameter('notification_upload_dir').DIRECTORY_SEPARATOR.$notification->getId();
    }
}
