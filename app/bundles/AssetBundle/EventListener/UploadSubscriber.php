<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UploadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private AssetModel $assetModel,
        protected Translator $translator,
        private FileUploadValidator $fileUploadValidator,
    ) {
    }

    public static function getSubscribedEvents(): array
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
    public function onPostUpload(PostUploadEvent $event): void
    {
        $request   = $event->getRequest()->request;
        $response  = $event->getResponse();
        $tempId    = basename($request->get('tempId'));
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
    public function onUploadValidation(ValidationEvent $event): void
    {
        $file                = $event->getFile();
        $extensions          = $this->coreParametersHelper->get('allowed_extensions');
        $configuredMimeTypes = $this->coreParametersHelper->get('allowed_mimetypes');
        $allowedMimeTypes    = array_intersect_key($configuredMimeTypes, array_flip($extensions));
        $maxSize             = $this->assetModel->getMaxUploadSize('B');

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

        if (array_key_exists(strtolower($file->getExtension()), array_change_key_case($configuredMimeTypes, CASE_LOWER))) {
            try {
                $this->checkMimeType($file->getMimeType(), $allowedMimeTypes, 'mautic.asset.asset.error.file.mimetype');
            } catch (FileInvalidException $e) {
                throw new ValidationException($e->getMessage());
            }
        }
    }

    /**
     * @param array<string,string> $allowedMimeTypes
     */
    private function checkMimeType(string $mimeType, array $allowedMimeTypes, string $extensionErrorMsg): void
    {
        if (!in_array(strtolower($mimeType), array_map('strtolower', $allowedMimeTypes), true)) {
            $error = $this->translator->trans($extensionErrorMsg, [
                '%fileMimetype%' => $mimeType,
                '%mimetypes%'    => implode(', ', $allowedMimeTypes),
            ], 'validators');

            throw new FileInvalidException($error);
        }
    }
}
