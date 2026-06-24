<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Service\Exception\InvalidPackageNameException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CampaignShareService
{
    // The marketplace copies the ZIP into its own storage on submit, so the file only
    // needs to be reachable for the short window between user clicking Publish on Mautic
    // and Submit on the marketplace publish page.
    public const SHARE_TTL_SECONDS = 3600;

    public const SHARE_DIR = 'campaign_share';

    public function __construct(
        private ExportHelper $exportHelper,
        private CoreParametersHelper $coreParametersHelper,
        private UrlGeneratorInterface $urlGenerator,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * Creates a shareable ZIP with composer.json, stores it under a transient token,
     * and returns the public download URL. The token is unguessable so no auth is needed.
     *
     * @param array<int|string, mixed> $exportData
     * @param array<int, string>       $assetList
     * @param array<string, mixed>     $metadata
     *
     * @throws InvalidPackageNameException
     */
    public function share(Campaign $campaign, array $exportData, array $assetList, array $metadata = []): string
    {
        $composerJson = $this->buildComposerJson($campaign, $metadata);

        $jsonOutput = json_encode([$exportData], JSON_PRETTY_PRINT);

        $zipFilePath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, '');
        $this->addComposerJsonToZip($zipFilePath, $composerJson);

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

        $token = $this->storeTransientZip($zipFilePath);

        return $this->urlGenerator->generate(
            'mautic_campaign_share_download',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     *
     * @throws InvalidPackageNameException
     */
    public function buildComposerJson(Campaign $campaign, array $metadata = []): array
    {
        $title   = $metadata['title'] ?? $campaign->getName();
        $vendor  = $metadata['vendorName'] ?? '';
        $name    = $this->toPackageName($title, $vendor);
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

    /**
     * Normalizes the submitted share form data into the metadata structure consumed
     * by share() and buildComposerJson().
     *
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     */
    public function buildShareMetadata(array $formData): array
    {
        $gallery = [];
        for ($i = 1; $i <= 8; ++$i) {
            $image = $formData['galleryImage'.$i] ?? null;
            if (null !== $image) {
                $gallery[] = [
                    'image' => $image,
                    'alt'   => $formData['galleryAlt'.$i] ?? '',
                ];
            }
        }

        return [
            'title'             => $formData['title'],
            'vendorName'        => $formData['vendorName'] ?? '',
            'headline'          => $formData['headline'] ?? '',
            'description'       => $formData['description'] ?? '',
            'keywords'          => $formData['keywords'] ?? '',
            'version'           => $formData['version'],
            'worksWithVersions' => $formData['worksWithVersions'] ?? [],
            'languages'         => $formData['languages'] ?? [],
            'bannerImage'       => $formData['bannerImage'] ?? null,
            'gallery'           => $gallery,
            'price'             => $formData['price'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $composerJson
     */
    public function addComposerJsonToZip(string $zipFilePath, array $composerJson): void
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($zipFilePath)) {
            $zip->addFromString('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->close();
        }
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
     * Moves the generated ZIP into the transient share directory under an unguessable
     * token. Returns the token, which is the lookup key for the download endpoint.
     */
    private function storeTransientZip(string $zipFilePath): string
    {
        $shareDir = $this->getShareDir();
        $this->filesystem->mkdir($shareDir, 0775);

        $token    = bin2hex(random_bytes(16));
        $destPath = $shareDir.'/'.$token.'.zip';

        // Filesystem::rename transparently falls back to copy+remove when crossing
        // filesystem boundaries (e.g. /tmp on a different mount than upload_dir).
        $this->filesystem->rename($zipFilePath, $destPath, true);

        $this->purgeExpiredShares($shareDir);

        return $token;
    }

    public function getShareDir(): string
    {
        $uploadDir = (string) $this->coreParametersHelper->get('upload_dir', 'media/files');

        return rtrim($uploadDir, '/').'/'.self::SHARE_DIR;
    }

    private function purgeExpiredShares(string $shareDir): void
    {
        $cutoff = time() - self::SHARE_TTL_SECONDS;
        foreach (glob($shareDir.'/*.zip') ?: [] as $file) {
            $mtime = @filemtime($file);
            if (false !== $mtime && $mtime < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * @throws InvalidPackageNameException
     */
    private function toPackageName(string $campaignName, string $vendor): string
    {
        $slug = strtolower(trim($campaignName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ('' === $slug) {
            $slug = 'campaign';
        }

        if ('' === trim($vendor)) {
            throw new InvalidPackageNameException('Vendor name is required to build a package name.');
        }

        return $vendor.'/'.$slug;
    }

    public function getMarketplaceUrl(): string
    {
        return rtrim($this->coreParametersHelper->get('marketplace_website_url', 'https://marketplace.mautic.org'), '/');
    }
}
