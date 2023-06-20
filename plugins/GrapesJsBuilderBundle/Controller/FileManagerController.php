<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileManagerController extends AjaxController
{
    /**
     * @return JsonResponse
     */
    public function uploadAction(Request $request, FileManager $fileManager)
    {
        return $this->sendJsonResponse(['data'=> $fileManager->uploadFiles($request)]);
    }

    /**
     * @param string $fileName
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, FileManager $fileManager)
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
