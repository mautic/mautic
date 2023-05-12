<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileManagerController extends AjaxController
{
    public function assetsAction(Request $request): JsonResponse
    {
        /** @var FileManager $fileManager */
        $fileManager = $this->get('grapesjsbuilder.helper.filemanager');

        return $this->sendJsonResponse([
            'assets'     => $fileManager->getImages($request->query->get('directory')),
            'directories'=> $fileManager->getDirectories(),
        ]);
    }

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

    /**
     * @param string $fileName
     *
     * @return JsonResponse
     */
    public function newDirectoryAction()
    {
        if (!$this->accessGranted('asset:folders:manage')) {
            return $this->accessDenied();
        }

        /** @var FileManager $fileManager */
        $fileManager = $this->get('grapesjsbuilder.helper.filemanager');

        $request         = json_decode($this->request->getContent(), true);
        $newDirectory    = $request['newDirectory'];
        $activeDirectory = $request['activeDirectory'];

        $createDirectory = $fileManager->createDirectory($activeDirectory, $newDirectory);

        if ('error' === $createDirectory['status']) {
            return $this->sendJsonResponse($createDirectory);
        }

        return $this->sendJsonResponse([
            'status'     => 'ok',
            'assets'     => $fileManager->getImages($activeDirectory),
            'directories'=> $fileManager->getDirectories(),
        ]);
    }
}
