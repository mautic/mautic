<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Service\Attribute\Required;

final class PublicController extends AbstractFormController
{
    private AssetRepository $assetRepository;

    #[Required]
    public function autowirePublicController(
        AssetRepository $assetRepository,
    ): void {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Handles public download of assets by slug.
     *
     * This method performs the initial validation of the slug, retrieves the
     *  corresponding Asset entity, and then delegates further response logic
     * (such as streaming or access denial) to `createAssetResponse()`.
     *
     * @throws ORMException
     */
    public function downloadAction(
        Request $request,
        AssetModel $model,
        string $slug,
    ): Response {
        try {
            $entity = $this->assetRepository->findOneByUuid($slug);
        } catch (NonUniqueResultException|EntityNotFoundException) {
            /**
             * Legacy slug lookup fallback.
             * - `{id}:{alias}` and
             * - `{id}:`.
             */
            $entity = $model->getEntityBySlugs($slug);
        }

        if (!$entity instanceof Asset) {
            return $this->notFound();
        }

        return $this->createAssetResponse($request, $model, $entity);
    }

    /**
     * Determines and returns the appropriate response for a resolved Asset entity.
     *
     * Flow:
     * - If access is not allowed → track download attempt and return 401
     * - If the asset is remote → track and redirect
     * - If the asset is local → track and return a file response
     *
     * @throws ORMException
     */
    private function createAssetResponse(
        Request $request,
        AssetModel $model,
        Asset $entity,
    ): Response {
        if (!$this->isAccessAllowed($entity)) {
            $model->trackDownload($entity, $request, 401);

            $this->throwAccessDenied();
        }

        if ($entity->isRemote()) {
            return $this->remoteRedirectResponse($model, $entity, $request);
        }

        return $this->localDownloadResponse($model, $entity, $request);
    }

    /**
     * Checks if the current user is allowed to access the given asset.
     *
     * - If an asset is published → allowed
     * - Else → must have view-own/view-other access based on ownership
     */
    private function isAccessAllowed(Asset $entity): bool
    {
        return $entity->isPublished()
            || $this->security->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy());
    }

    /**
     * Tracks the download and returns a redirect to the asset's remote location.
     *
     * @throws ORMException
     */
    private function remoteRedirectResponse(AssetModel $model, Asset $entity, Request $request): RedirectResponse
    {
        $model->trackDownload($entity, $request);

        return new RedirectResponse($entity->getRemotePath());
    }

    /**
     * Tracks the download and builds a response for a locally hosted asset.
     *
     * Includes:
     * - Resolve the local file path
     * - Track successful or failed download attempts
     * - Set appropriate content-type headers
     * - Set Content-Disposition (inline or attachment) with the original file name
     * - Apply robot meta headers when required
     * - Return 404 if the file cannot be read
     */
    private function localDownloadResponse(
        AssetModel $model,
        Asset $entity,
        Request $request,
    ): Response {
        try {
            $entity->setUploadDir($this->coreParametersHelper->get('upload_dir'));
            $contents = $entity->getFileContents();
            $model->trackDownload($entity, $request);
        } catch (FileNotFoundException) {
            $model->trackDownload($entity, $request, 404);

            return $this->notFound();
        }

        $response = new Response($contents);
        $response->headers->set('Content-Type', $entity->getFileMimeType());

        if ($entity->getDisallow()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        $stream = $request->get('stream', in_array(
            $entity->getExtension(),
            $this->coreParametersHelper->get('streamed_extensions')
        ));

        // Send the original file name also for streamed files, otherwise browsers name the saved file after the URL
        $filename         = str_replace(['/', '\\'], '_', (string) $entity->getOriginalFileName());
        $filenameFallback = preg_replace('/[^\x20-\x7e]|%/u', '_', $filename);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                $stream ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
                $filenameFallback
            )
        );

        return $response;
    }
}
