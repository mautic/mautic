<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;

/**
 * Class UploadSubscriber.
 */
class UploadSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AssetModel
     */
    protected $assetModel;

    /**
     * @var FileUploadValidator
     */
    private $fileUploadValidator;

    /**
     * @param CoreParametersHelper $coreParametersHelper
     * @param AssetModel           $assetModel
     * @param FileUploadValidator  $fileUploadValidator
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, AssetModel $assetModel, FileUploadValidator $fileUploadValidator)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->assetModel           = $assetModel;
        $this->fileUploadValidator  = $fileUploadValidator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            UploadEvents::POST_UPLOAD => ['onPostUpload', 0],
            UploadEvents::VALIDATION  => ['onUploadValidation', 0],
        ];
    }

    /**
     * Moves upladed file to temporary directory where it can be found later
     * and all uploaded files in there cleared. Also sets file name to the response.
     *
     * @param PostUploadEvent $event
     */
    public function onPostUpload(PostUploadEvent $event)
    {
        $request   = $event->getRequest()->request;
        $response  = $event->getResponse();
        $tempId    = $request->get('tempId');
        $file      = $event->getFile();
        $config    = $event->getConfig();
        $uploadDir = $config['storage']['directory'];
        $tmpDir    = $uploadDir.'/tmp/'.$tempId;

        // Move uploaded file to temporary folder
        $file->move($tmpDir);

        // Set resposnse data
        $response['state']       = 1;
        $response['tmpFileName'] = $file->getBasename();
    }

    /**
     * Validates file before upload.
     *
     * @param ValidationEvent $event
     *
     * @throws ValidationException
     */
    public function onUploadValidation(ValidationEvent $event)
    {
        $file       = $event->getFile();
        $extensions = $this->coreParametersHelper->getParameter('allowed_extensions');
        $maxSize    = $this->assetModel->getMaxUploadSize('B');

        if ($file === null) {
            return;
        }

        try {
            $this->fileUploadValidator->checkFileSize($file->getSize(), $maxSize, 'mautic.asset.asset.error.file.size');
        } catch (FileInvalidException $e) {
            throw new ValidationException($e->getMessage());
        }

        try {
            $this->fileUploadValidator->checkExtension($file->getExtension(), $extensions, 'mautic.asset.asset.error.file.extension');
        } catch (FileInvalidException $e) {
            throw new ValidationException($e->getMessage());
        }
    }
}
