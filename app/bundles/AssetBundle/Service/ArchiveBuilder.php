<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Helper\InputHelper;

final class ArchiveBuilder
{
    /**
     * @param array<Asset> $assets
     */
    public function buildArchive(array $assets): string
    {
        $zipPath    = $this->createTempZipFile();
        $zipArchive = $this->openZipArchive($zipPath);

        try {
            $this->addAssetsToArchive($zipArchive, $assets);
            $this->closeZipArchive($zipArchive, $zipPath);
        } catch (\Throwable $e) {
            $this->cleanupZipArchive($zipArchive, $zipPath);
            throw $e;
        }

        return $zipPath;
    }

    private function createTempZipFile(): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'mautic_asset_batch_');

        if (false === $zipPath) {
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
        }

        return $zipPath;
    }

    private function openZipArchive(string $zipPath): \ZipArchive
    {
        $zipArchive = new \ZipArchive();

        if (true !== $zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            @unlink($zipPath);
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
        }

        return $zipArchive;
    }

    /**
     * @param array<Asset> $assets
     */
    private function addAssetsToArchive(\ZipArchive $zipArchive, array $assets): void
    {
        $usedNames = [];

        foreach ($assets as $asset) {
            $filename = $this->generateFilename($asset, $usedNames);

            if ($asset->isRemote()) {
                $this->addRemoteAsset($zipArchive, $asset, $filename);
            } else {
                $this->addLocalAsset($zipArchive, $asset, $filename);
            }
        }
    }

    private function addRemoteAsset(\ZipArchive $zipArchive, Asset $asset, string $filename): void
    {
        $content = @file_get_contents($asset->getFilePath());

        if (false === $content || false === $zipArchive->addFromString($filename, $content)) {
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.unavailable');
        }
    }

    private function addLocalAsset(\ZipArchive $zipArchive, Asset $asset, string $filename): void
    {
        $absolutePath = $asset->getAbsolutePath();

        if (empty($absolutePath) || false === $zipArchive->addFile($absolutePath, $filename)) {
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.unavailable');
        }
    }

    private function closeZipArchive(\ZipArchive $zipArchive, string $zipPath): void
    {
        if (true !== $zipArchive->close()) {
            @unlink($zipPath);
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
        }
    }

    private function cleanupZipArchive(\ZipArchive $zipArchive, string $zipPath): void
    {
        if ($zipArchive->numFiles > 0) {
            $zipArchive->close();
        }
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }
    }

    /**
     * @param array<string> $usedNames
     */
    private function generateFilename(Asset $asset, array &$usedNames): string
    {
        $originalFileName = $asset->getOriginalFileName();
        $filename         = $originalFileName ?: ($asset->getTitle() ?: (string) $asset->getId());

        $filename = InputHelper::transliterateFilename($filename);

        $finalName   = $this->ensureUniqueFilename($filename, $usedNames);
        $usedNames[] = mb_strtolower($finalName);

        return $finalName;
    }

    /**
     * @param array<string> $usedNames
     */
    private function ensureUniqueFilename(string $filename, array &$usedNames): string
    {
        $finalName = $filename;
        $index     = 1;

        while (in_array(mb_strtolower($finalName), $usedNames, true)) {
            $finalName = $this->generateUniqueName($filename, $index);
            ++$index;
        }

        return $finalName;
    }

    private function generateUniqueName(string $filename, int $index): string
    {
        $pathInfo  = pathinfo($filename);
        $baseName  = $pathInfo['filename'] ?? $filename;
        $ext       = $pathInfo['extension'] ?? '';

        return $ext ? sprintf('%s (%d).%s', $baseName, $index, $ext) : sprintf('%s (%d)', $baseName, $index);
    }
}
