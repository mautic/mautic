<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadTrackController
{
    public function __construct(
        private AssetModel $assetModel,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * Handle download tracking request.
     * Creates a remote asset if the URL doesn't exist, returns the tracking URL.
     */
    public function trackAction(Request $request): Response
    {
        $url = $request->request->get('url');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => 'Invalid URL'], Response::HTTP_BAD_REQUEST);
        }

        // Check if this is a trackable file extension (use Mautic's configured allowed_extensions)
        $extension         = $this->getFileExtension($url);
        $allowedExtensions = $this->coreParametersHelper->get('allowed_extensions');

        if (!in_array(strtolower($extension), $allowedExtensions, true)) {
            return new JsonResponse(['skip' => true, 'reason' => 'Not a trackable file type']);
        }

        // Check if URL is already a local Mautic asset from THIS instance (by ID in URL)
        $localAsset = $this->findLocalAssetByUrl($url);
        if (null !== $localAsset) {
            // Already a tracked local asset - let it go through normally
            return new JsonResponse(['skip' => true, 'reason' => 'Already a local Mautic asset']);
        }

        // Check if URL already exists as a remote asset
        $existingRemoteAsset = $this->findRemoteAssetByUrl($url);
        if (null !== $existingRemoteAsset) {
            // Already tracked as remote asset - return existing tracking URL
            $trackingUrl = $this->assetModel->generateUrl($existingRemoteAsset, true);

            return new JsonResponse([
                'success'     => true,
                'trackingUrl' => $trackingUrl,
                'assetId'     => $existingRemoteAsset->getId(),
                'existing'    => true,
            ]);
        }

        // Create new remote asset
        $asset = $this->createRemoteAsset($url);

        if (null === $asset) {
            return new JsonResponse(['error' => 'Failed to create asset'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Return the Mautic asset download URL for tracking
        $trackingUrl = $this->assetModel->generateUrl($asset, true);

        return new JsonResponse([
            'success'     => true,
            'trackingUrl' => $trackingUrl,
            'assetId'     => $asset->getId(),
        ]);
    }

    private function getFileExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            return '';
        }

        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Check if URL is a local asset download URL from THIS Mautic instance.
     * Pattern: {site_url}/asset/{id}:{alias}.
     */
    private function findLocalAssetByUrl(string $url): ?Asset
    {
        $siteUrl = rtrim($this->coreParametersHelper->get('site_url'), '/');

        // Must start with our site URL
        if (!str_starts_with($url, $siteUrl)) {
            return null;
        }

        // Extract asset ID from URL pattern /asset/{id}:{alias}
        if (!preg_match('#/asset/(\d+):#', $url, $matches)) {
            return null;
        }

        $assetId = (int) $matches[1];

        // Verify asset exists in our database
        return $this->assetModel->getEntity($assetId);
    }

    /**
     * Find existing remote asset by its remotePath.
     */
    private function findRemoteAssetByUrl(string $url): ?Asset
    {
        $repo  = $this->assetModel->getRepository();
        $asset = $repo->findOneBy(['remotePath' => $url]);

        return $asset instanceof Asset ? $asset : null;
    }

    /**
     * Create a new remote asset for the given URL.
     */
    private function createRemoteAsset(string $url): ?Asset
    {
        // Create new remote asset - title will be auto-set by Asset::setFileNameFromRemote()
        // which is called during preUpload() when remotePath is set and title is null
        $asset = new Asset();
        $asset->setStorageLocation('remote');
        $asset->setRemotePath($url);
        $asset->setIsPublished(true);

        try {
            $this->assetModel->saveEntity($asset);

            return $asset;
        } catch (\Exception $e) {
            return null;
        }
    }
}
