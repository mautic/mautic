<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Service;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CampaignShareService
{
    public function __construct(
        private ExportHelper $exportHelper,
        private AssetModel $assetModel,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * Creates a shareable ZIP with composer.json, saves it as an Asset,
     * and returns the public download URL.
     *
     * @param array<int|string, mixed> $exportData
     * @param array<int, string>       $assetList
     * @param array<string, mixed>     $metadata
     */
    public function share(Campaign $campaign, array $exportData, array $assetList, array $metadata = []): string
    {
        $composerJson = $this->buildComposerJson($campaign, $metadata);

        $jsonOutput = json_encode([$exportData], JSON_PRETTY_PRINT);

        $zipFilePath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, '', $composerJson);

        // Add banner image
        $bannerImage = $metadata['bannerImage'] ?? null;
        if ($bannerImage instanceof UploadedFile) {
            $this->addImageToZip($zipFilePath, $bannerImage, 'banner');
        }

        // Add gallery images
        $gallery = $metadata['gallery'] ?? [];
        foreach ($gallery as $index => $item) {
            $image = $item['image'] ?? null;
            if ($image instanceof UploadedFile) {
                $alt = $item['alt'] ?? '';
                $this->addImageToZip($zipFilePath, $image, 'gallery/image_'.($index + 1));
                if ('' !== $alt) {
                    $this->addTextToZip($zipFilePath, 'gallery/image_'.($index + 1).'.alt.txt', $alt);
                }
            }
        }

        $asset = $this->createAssetFromZip($campaign, $zipFilePath, $metadata);

        return $this->assetModel->generateUrl($asset, true);
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public function buildComposerJson(Campaign $campaign, array $metadata = []): array
    {
        $title   = $metadata['title'] ?? $campaign->getName();
        $name    = $this->toPackageName($title);
        $version = $metadata['version'] ?? '1.0.0';

        $worksWithVersions = $metadata['worksWithVersions'] ?? [];
        $minimumVersion    = [] !== $worksWithVersions ? min($worksWithVersions) : '5.0';

        $keywords = [];
        if (!empty($metadata['keywords'])) {
            $keywords = array_map('trim', explode(',', (string) $metadata['keywords']));
            $keywords = array_filter($keywords);
        }

        $extra = [
            'mautic' => [
                'campaign-uuid'    => $campaign->getUuid(),
                'display-name'     => $title,
                'headline'         => $metadata['headline'] ?? '',
                'minimum-version'  => $minimumVersion,
                'works-with'       => array_values($worksWithVersions),
                'languages'        => $metadata['languages'] ?? [],
            ],
        ];

        $price = $metadata['price'] ?? null;
        if (null !== $price && $price > 0) {
            $extra['mautic']['price'] = [
                'amount'   => (float) $price,
                'currency' => 'EUR',
            ];
        }

        $composerJson = [
            'name'        => $name,
            'description' => $metadata['description'] ?? $campaign->getDescription() ?: '',
            'type'        => 'mautic-resource',
            'version'     => $version,
        ];

        if ([] !== $keywords) {
            $composerJson['keywords'] = array_values($keywords);
        }

        $composerJson['extra'] = $extra;

        return $composerJson;
    }

    private function addImageToZip(string $zipFilePath, UploadedFile $image, string $baseName): void
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($zipFilePath)) {
            $ext = $image->guessExtension() ?: 'png';
            $zip->addFile($image->getPathname(), $baseName.'.'.$ext);
            $zip->close();
        }
    }

    private function addTextToZip(string $zipFilePath, string $entryName, string $content): void
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($zipFilePath)) {
            $zip->addFromString($entryName, $content);
            $zip->close();
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function createAssetFromZip(Campaign $campaign, string $zipFilePath, array $metadata = []): Asset
    {
        $uploadDir = $this->coreParametersHelper->get('upload_dir', 'media/files');
        $fileName  = 'campaign_share_'.sha1(uniqid((string) mt_rand(), true)).'.zip';
        $destPath  = $uploadDir.'/'.$fileName;

        copy($zipFilePath, $destPath);
        @unlink($zipFilePath);

        $title = $metadata['title'] ?? $campaign->getName();

        $asset = new Asset();
        $asset->setTitle('Campaign: '.$title);
        $asset->setDescription($metadata['description'] ?? 'Shared campaign export for the Mautic Marketplace.');
        $asset->setStorageLocation('local');
        $asset->setPath($fileName);
        $asset->setOriginalFileName($title.'.zip');
        $asset->setExtension('zip');
        $asset->setMime('application/zip');
        $asset->setSize(filesize($destPath) ?: 0);
        $asset->setIsPublished(true);
        $asset->setUploadDir($uploadDir);

        $this->assetModel->saveEntity($asset);

        return $asset;
    }

    private function toPackageName(string $campaignName): string
    {
        $slug = strtolower(trim($campaignName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ('' === $slug) {
            $slug = 'campaign';
        }

        return 'mautic/'.$slug;
    }

    public function getMarketplaceUrl(): string
    {
        return rtrim($this->coreParametersHelper->get('marketplace_website_url', 'https://marketplace.mautic.org'), '/');
    }
}
