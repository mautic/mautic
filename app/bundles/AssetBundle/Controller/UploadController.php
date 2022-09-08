<?php

namespace Mautic\AssetBundle\Controller;

use Oneup\UploaderBundle\Controller\DropzoneController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends DropzoneController
{
    private \Symfony\Component\HttpFoundation\RequestStack $requestStack;
    private \Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, \Oneup\UploaderBundle\Uploader\Storage\StorageInterface $storage, \Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface $errorHandler, array $config, string $type, \Symfony\Component\HttpFoundation\RequestStack $requestStack, \Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator)
    {
        $this->requestStack = $requestStack;
        parent::__construct($container, $storage, $errorHandler, $config, $type);
        $this->dataCollectorTranslator = $dataCollectorTranslator;
    }

    public function upload(): JsonResponse
    {
        /** @var Request $request */
        $request  = $this->requestStack->getCurrentRequest();
        $response = new EmptyResponse();
        $files    = $this->getFiles($request->files);

        if (!empty($files)) {
            foreach ($files as $file) {
                try {
                    $this->handleUpload($file, $response, $request);
                } catch (UploadException $e) {
                    $this->errorHandler->addException($response, $e);
                } catch (\Exception $e) {
                    error_log($e);

                    $error = new UploadException($this->dataCollectorTranslator->trans('mautic.asset.error.file.failed'));
                    $this->errorHandler->addException($response, $error);
                }
            }
        } else {
            $error = new UploadException($this->dataCollectorTranslator->trans('mautic.asset.error.file.failed'));
            $this->errorHandler->addException($response, $error);
        }

        return $this->createSupportedJsonResponse($response->assemble());
    }
}
