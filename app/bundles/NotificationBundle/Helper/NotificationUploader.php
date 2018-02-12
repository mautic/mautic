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

    /**
     * @var array
     */
    private $uploadFilesName = ['actionButtonIcon1', 'actionButtonIcon2', 'icon', 'image'];

    public function __construct(FileUploader $fileUploader, CoreParametersHelper $coreParametersHelper)
    {
        $this->fileUploader         = $fileUploader;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param Notification $notification
     * @param $request
     * @param Form $form
     */
    public function uploadFiles(Notification $notification, $request, Form $form)
    {
        $files         = $request->files->all()['notification'];

        $uploadedFiles = [];
        $deteledFiles  = [];
        foreach ($this->getUploadFilesName() as $fileName) {
            $uploadDir    = $this->getUploadDir($notification);

            // Delete file
            if (!empty($form->get($fileName.'_delete')->getData())) {
                $this->fileUploader->delete($uploadDir.DIRECTORY_SEPARATOR.$this->getEntityVar($notification, $fileName));
                $this->getEntityVar($notification, $fileName, 'set', '');
            }

            /* @var UploadedFile $file */
            if (empty($files[$fileName])) {
                continue;
            }
            $file = $files[$fileName];

            try {
                $uploadedFile = $this->fileUploader->upload($uploadDir, $file);
                $this->getEntityVar($notification, $fileName, 'set', $uploadedFile);
            } catch (FileUploadException $e) {
                $this->fileUploader->delete($uploadDir.DIRECTORY_SEPARATOR.$uploadedFile);
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
    public function getFullUrl($notification, $key)
    {
        if ($fileName = $this->getEntityVar($notification, $key)) {
            return $this->coreParametersHelper->getParameter('site_url').DIRECTORY_SEPARATOR.$this->coreParametersHelper->getParameter('notification_upload_dir').DIRECTORY_SEPARATOR.$notification->getId().DIRECTORY_SEPARATOR.$fileName;
        }
    }

    /**
     * @param object $entity
     * @param string $key
     * @param string $action
     */
    public function getEntityVar($entity, $key, $action = 'get', $value = '')
    {
        $var = $action.ucfirst($key);
        if ($action == 'get') {
            return $entity->$var();
        } else {
            $entity->$var((string) $value);
        }
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

    /**
     * @return array
     */
    public function getUploadFilesName()
    {
        return $this->uploadFilesName;
    }
}
