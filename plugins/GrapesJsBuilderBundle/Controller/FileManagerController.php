<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileManagerController extends AjaxController
{
    /**
     * @return JsonResponse
     */
    public function uploadAction()
    {
        /** @var FileManager $fileManager */
        $fileManager = $this->get('grapesjsbuilder.helper.filemanager');

        return $this->sendJsonResponse(['data'=> $fileManager->uploadFiles($this->request)]);
    }

    /**
     * @param string $fileName
     *
     * @return JsonResponse
     */
    public function deleteAction()
    {
        /** @var FileManager $fileManager */
        $fileManager = $this->get('grapesjsbuilder.helper.filemanager');

        $fileName = $this->request->get('filename');

        $fileManager->deleteFile($fileName);

        return $this->sendJsonResponse(['success'=> true]);
    }
}
