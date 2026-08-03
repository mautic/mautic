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
    private const DEFAULT_PAGE  = 1;

    private const DEFAULT_LIMIT = 20;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected \Mautic\CoreBundle\Factory\ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly FileManager $fileManager,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function uploadAction(Request $request): Response
    {
        try {
            $response = $this->sendJsonResponse(['data'=> $this->fileManager->uploadFiles($request)]);
        } catch (FileUploadException $error) {
            return new Response($error->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    public function deleteAction(Request $request): JsonResponse
    {
        $fileName = basename($request->get('filename'));
        $filePath = $this->fileManager->getCompleteFilePath($fileName);

        if (!file_exists($filePath) || !exif_imagetype($filePath)) {
            return $this->sendJsonResponse(['success'=> false]);
        }

        $this->fileManager->deleteFile($fileName);

        return $this->sendJsonResponse(['success'=> true]);
    }

    /**
     * @deprecated since Mautic 5.2, to be removed in 6.0. Use FileManagerController::getMediaAction instead
     */
    public function assetsAction(): JsonResponse
    {
        return $this->sendJsonResponse([
            'data' => $this->fileManager->getImages(),
        ]);
    }

    public function getMediaAction(Request $request): JsonResponse
    {
        $page  = $request->query->getInt('page', self::DEFAULT_PAGE);
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        return $this->sendJsonResponse($this->fileManager->getMediaFiles($page, $limit));
    }
}
