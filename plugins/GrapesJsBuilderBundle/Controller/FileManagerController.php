<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileManagerController extends AjaxController
{
    public function uploadAction(Request $request, FileManager $fileManager): JsonResponse
    {
        return $this->sendJsonResponse(['data'=> $fileManager->uploadFiles($request)]);
    }

    public function deleteAction(Request $request, FileManager $fileManager): JsonResponse
    {
        $fileName = $request->get('filename');

        $fileManager->deleteFile($fileName);

        return $this->sendJsonResponse(['success'=> true]);
    }

    public function assetsAction(FileManager $fileManager): JsonResponse
    {
        return $this->sendJsonResponse([
            'data' => $fileManager->getImages(),
        ]);
    }
}
