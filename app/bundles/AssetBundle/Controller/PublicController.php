<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends AbstractFormController
{
    /**
     * Handles public download of assets by slug.
     *
     * This method performs the initial validation of the slug, retrieves the
     *  corresponding Asset entity, and then delegates further response logic
     *  (such as redirects, streaming, or access denial) to `createAssetResponse()`.
     *
     * @throws ORMException
     */
    public function downloadAction(
        Request $request,
        CoreParametersHelper $parametersHelper,
        AssetModel $model,
        string $slug,
    ): Response {
        try {
            $entity = $model->getRepository()->findByIdAndAlias($slug);
        } catch (NonUniqueResultException|EntityNotFoundException|\InvalidArgumentException) {
            return $this->notFound();
        }

        return $this->createAssetResponse($request, $parametersHelper, $model, $entity);
    }

    /**
     * Determines and returns the appropriate response based on the given Asset entity.
     *
     * Logic:
     * - If entity is missing → return 404
     * - If access is not allowed → track and return 401
     * - If remote asset → track and redirect to remote URL
     * - If local asset → track and stream file
     *
     * @throws ORMException
     */
    private function createAssetResponse(Request $request, CoreParametersHelper $parametersHelper, AssetModel $model, ?Asset $entity): Response
    {
        if (!$this->isAccessAllowed($entity)) {
            $model->trackDownload($entity, $request, 401);
            $response = $this->accessDenied();
        } elseif ($entity->isRemote()) {
            $response = $this->remoteRedirectResponse($model, $entity, $request);
        } else {
            $response = $this->localDownloadResponse($model, $entity, $request, $parametersHelper);
        }

        return $response;
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
    private function remoteRedirectResponse(AssetModel $model, Asset $entity, Request $request): Response
    {
        $model->trackDownload($entity, $request);

        return new RedirectResponse($entity->getRemotePath());
    }

    /**
     * Tracks the download and builds a streamed response for a locally hosted file.
     *
     * Includes:
     * - Setting correct content-type headers
     * - Optionally forcing download via content-disposition
     * - Applying robot meta-headers if required
     * - Handling missing or unreadable file exceptions with a 404
     */
    private function localDownloadResponse(
        AssetModel $model,
        Asset $entity,
        Request $request,
        CoreParametersHelper $parametersHelper,
    ): Response {
        try {
            $entity->setUploadDir($parametersHelper->get('upload_dir'));
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

        if (!$stream) {
            $response->headers->set(
                'Content-Disposition',
                'attachment;filename="'.$entity->getOriginalFileName().'"'
            );
        }

        return $response;
    }
}
