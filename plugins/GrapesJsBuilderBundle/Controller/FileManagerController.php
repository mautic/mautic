<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use Mautic\CoreBundle\Exception\FileUploadException;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FileManagerController extends AjaxController
{
    private const int DEFAULT_PAGE  = 1;

    private const int DEFAULT_LIMIT = 20;

    public function uploadAction(Request $request, FileManager $fileManager): Response
    {
        try {
            $response = $this->sendJsonResponse(['data'=> $fileManager->uploadFiles($request)]);
        } catch (FileUploadException $error) {
            return new Response($error->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $response;
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

    public function getMediaAction(Request $request, FileManager $fileManager): JsonResponse
    {
        $page  = $request->query->getInt('page', self::DEFAULT_PAGE);
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        return $this->sendJsonResponse($fileManager->getMediaFiles($page, $limit));
    }
}
