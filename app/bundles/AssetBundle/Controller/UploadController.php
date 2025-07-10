<?php

namespace Mautic\AssetBundle\Controller;

use Oneup\UploaderBundle\Controller\DropzoneController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadController extends DropzoneController
{
    private TranslatorInterface $translator;

    public function upload(): JsonResponse
    {
        $request  = $this->getRequest();
        $response = new EmptyResponse();
        $files    = $this->getFiles($request->files);
        $this->setTranslator($this->container->get('translator'));

        if (!empty($files)) {
            foreach ($files as $file) {
                try {
                    $this->handleUpload($file, $response, $request);
                } catch (UploadException $e) {
                    $this->errorHandler->addException($response, $e);
                } catch (\Exception $e) {
                    error_log($e);
                    $error = new UploadException($this->translator->trans('mautic.asset.error.file.failed'));
                    $this->errorHandler->addException($response, $error);
                }
            }
        } else {
            $error = new UploadException($this->translator->trans('mautic.asset.error.file.failed'));
            $this->errorHandler->addException($response, $error);
        }

        return $this->createSupportedJsonResponse($response->assemble());
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
