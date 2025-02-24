<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileManagerController extends AjaxController
{
    private const DEFAULT_PAGE  = 1;
    private const DEFAULT_LIMIT = 20;

    public function uploadAction(Request $request, FileManager $fileManager): JsonResponse
    {
        return $this->sendJsonResponse(['data'=> $fileManager->uploadFiles($request)]);
    }

    public function deleteAction(Request $request, FileManager $fileManager): JsonResponse
    {
        $fileName = basename($request->get('filename'));
        $filePath = $fileManager->getCompleteFilePath($fileName);

        if (!file_exists($filePath) || !exif_imagetype($filePath)) {
            return $this->sendJsonResponse(['success'=> false]);
        }

        $fileManager->deleteFile($fileName);

        return $this->sendJsonResponse(['success'=> true]);
    }

    /**
     * @deprecated since Mautic 5.2, to be removed in 6.0. Use FileManagerController::getMediaAction instead
     */
    public function assetsAction(FileManager $fileManager): JsonResponse
    {
        return $this->sendJsonResponse([
            'data' => $fileManager->getImages(),
        ]);
    }

    public function getMediaAction(Request $request, FileManager $fileManager): JsonResponse
    {
        $page  = $request->query->getInt('page', self::DEFAULT_PAGE);
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        return $this->sendJsonResponse($fileManager->getMediaFiles($page, $limit));
    }
}
