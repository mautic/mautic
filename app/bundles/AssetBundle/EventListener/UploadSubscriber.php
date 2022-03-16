<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UploadSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * @var FileUploadValidator
     */
    private $fileUploadValidator;

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
     * @throws ValidationException
     */
    public function onUploadValidation(ValidationEvent $event)
    {
        $file       = $event->getFile();
        $extensions = $this->coreParametersHelper->get('allowed_extensions');
        $maxSize    = $this->assetModel->getMaxUploadSize('B');

        if (null === $file) {
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
