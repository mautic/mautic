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

final readonly class CampaignShareService
{
    // The marketplace copies the ZIP into its own storage on submit, so the file only
    // needs to be reachable for the short window between user clicking Publish on Mautic
    // and Submit on the marketplace publish page.
    public const int SHARE_TTL_SECONDS = 3600;

    public const string SHARE_DIR = 'campaign_share';

    // Uploaded banner/gallery images are parked here when the share form fails
    // validation, so a corrected re-submit doesn't force the user to pick them again.
    public const string PENDING_DIR = 'campaign_share/pending';

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
        $zipFilePath = $this->buildShareZip($campaign, $exportData, $assetList, $metadata);

        $token = $this->storeTransientZip($zipFilePath);

        // The ZIP itself was moved into the share dir by storeTransientZip(); only the
        // now-empty per-request temp directory is left to clean up.
        $this->filesystem->remove(\dirname($zipFilePath));

        return $this->urlGenerator->generate(
            'mautic_campaign_share_download',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    /**
     * Builds the shareable ZIP (entity data, assets, composer.json, banner and gallery
     * images) and returns its path. Used by both the publish flow and the share form's
     * Download button so they produce identical archives.
     *
     * @param array<int|string, mixed> $exportData
     * @param array<int, string>       $assetList
     * @param array<string, mixed>     $metadata
     *
     * @throws InvalidPackageNameException
     */
    public function buildShareZip(Campaign $campaign, array $exportData, array $assetList, array $metadata = []): string
    {
        $composerJson = $this->buildComposerJson($campaign, $metadata);

        $jsonOutput = json_encode([$exportData], JSON_PRETTY_PRINT);

        // Each share/export gets its own directory, never the shared ExportHelper default
        // (sys_get_temp_dir().'/entity_data.zip'), so concurrent requests can't unlink or
        // overwrite one another's archive.
        $tempDir = sys_get_temp_dir().'/mautic_campaign_share_'.bin2hex(random_bytes(16));
        $this->filesystem->mkdir($tempDir, 0700);

        $zipFilePath = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, $tempDir);
        $this->addComposerJsonToZip($zipFilePath, $composerJson);

        // Banner and gallery live under assets/ so the shared archive keeps the same
        // layout as a plain campaign export and the import flow restores the images
        // into the media directory.
        $bannerImage = $metadata['bannerImage'] ?? null;
        if ($bannerImage instanceof UploadedFile) {
            $this->addImageToZip($zipFilePath, $bannerImage, 'assets/banner');
        }

        $gallery = $metadata['gallery'] ?? [];
        foreach ($gallery as $index => $item) {
            $image = $item['image'] ?? null;
            if ($image instanceof UploadedFile) {
                $alt = $item['alt'] ?? '';
                $this->addImageToZip($zipFilePath, $image, 'assets/gallery/image_'.($index + 1));
                if ('' !== $alt) {
                    $this->addTextToZip($zipFilePath, 'assets/gallery/image_'.($index + 1).'.alt.txt', $alt);
                }
            }
        }

        return $zipFilePath;
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
            $keywords = array_map(trim(...), explode(',', (string) $metadata['keywords']));
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
                    'alt'   => $this->plainText($formData['galleryAlt'.$i] ?? ''),
                ];
            }
        }

        return [
            'title'             => $this->plainText($formData['title']),
            'vendorName'        => $this->plainText($formData['vendorName'] ?? ''),
            'headline'          => $this->plainText($formData['headline'] ?? ''),
            'description'       => $formData['description'] ?? '',
            'keywords'          => $this->plainText($formData['keywords'] ?? ''),
            'version'           => $this->plainText($formData['version']),
            'worksWithVersions' => $this->plainTextList($formData['worksWithVersions'] ?? []),
            'languages'         => $this->plainTextList($formData['languages'] ?? []),
            'bannerImage'       => $formData['bannerImage'] ?? null,
            'gallery'           => $gallery,
            'price'             => $formData['price'] ?? null,
        ];
    }

    /**
     * Strips markup from the short metadata fields that end up in composer.json.
     *
     * These are read back by the marketplace and by anything else that consumes the package, so
     * they shouldn't be able to carry markup out of here. Description is left alone on purpose —
     * it is markdown, rendered with HTML input escaped, and stripping tags would mangle it.
     */
    private function plainText(mixed $value): string
    {
        if (!\is_string($value)) {
            return '';
        }

        // strip_tags leaves the stray angle brackets a malformed tag leaves behind, and these
        // fields never legitimately contain them.
        return trim(str_replace(['<', '>'], '', strip_tags($value)));
    }

    /**
     * @return list<string>
     */
    private function plainTextList(mixed $values): array
    {
        if (!\is_array($values)) {
            return [];
        }

        $clean = [];
        foreach ($values as $value) {
            $text = $this->plainText($value);
            if ('' !== $text) {
                $clean[] = $text;
            }
        }

        return $clean;
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

    /**
     * Parks an uploaded image under an unguessable token so it survives a failed
     * validation round-trip (browsers never repopulate file inputs). Returns the
     * token and the original client filename for display in the re-rendered form.
     *
     * @return array{token: string, name: string}
     */
    public function stashImage(UploadedFile $file): array
    {
        $pendingDir = $this->getPendingDir();
        $this->filesystem->mkdir($pendingDir, 0775);

        $token = bin2hex(random_bytes(16));
        $name  = $file->getClientOriginalName();

        $file->move($pendingDir, $token.'.img');
        $this->filesystem->dumpFile($pendingDir.'/'.$token.'.json', (string) json_encode([
            'originalName' => $name,
            'mimeType'     => $file->getClientMimeType(),
        ]));

        $this->purgeExpiredPendingImages($pendingDir);

        return ['token' => $token, 'name' => $name];
    }

    /**
     * Rebuilds an UploadedFile from a stashed image; null when the token is invalid,
     * expired, or was never set — callers then simply treat the image as absent.
     */
    public function restoreStashedImage(?string $token): ?UploadedFile
    {
        $meta = $this->readStashMetadata($token);
        if (null === $meta) {
            return null;
        }

        return new UploadedFile(
            $this->getPendingDir().'/'.$token.'.img',
            $meta['originalName'],
            $meta['mimeType'],
            null,
            true,
        );
    }

    /**
     * Original filename of a stashed image, for showing "kept: <name>" next to the
     * file input when the form re-renders after failed validation.
     */
    public function stashedImageName(?string $token): ?string
    {
        return $this->readStashMetadata($token)['originalName'] ?? null;
    }

    /**
     * @return array{originalName: string, mimeType: string}|null
     */
    private function readStashMetadata(?string $token): ?array
    {
        // The token is user-supplied on re-submit; the strict format check keeps it
        // from being abused as a path traversal into the upload directory.
        if (null === $token || 1 !== preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $pendingDir = $this->getPendingDir();
        if (!is_file($pendingDir.'/'.$token.'.img') || !is_file($pendingDir.'/'.$token.'.json')) {
            return null;
        }

        $meta = json_decode((string) file_get_contents($pendingDir.'/'.$token.'.json'), true);
        if (!\is_array($meta) || !\is_string($meta['originalName'] ?? null) || !\is_string($meta['mimeType'] ?? null)) {
            return null;
        }

        return ['originalName' => $meta['originalName'], 'mimeType' => $meta['mimeType']];
    }

    private function getPendingDir(): string
    {
        $uploadDir = (string) $this->coreParametersHelper->get('upload_dir', 'media/files');

        return rtrim($uploadDir, '/').'/'.self::PENDING_DIR;
    }

    private function purgeExpiredPendingImages(string $pendingDir): void
    {
        $cutoff = time() - self::SHARE_TTL_SECONDS;
        foreach (glob($pendingDir.'/*.{img,json}', GLOB_BRACE) ?: [] as $file) {
            $mtime = @filemtime($file);
            if (false !== $mtime && $mtime < $cutoff) {
                @unlink($file);
            }
        }
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
