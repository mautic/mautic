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
        $zipPath = tempnam(sys_get_temp_dir(), 'mautic_asset_batch_');

        if (false === $zipPath) {
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
        }

        $zipArchive = new \ZipArchive();

        if (true !== $zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            @unlink($zipPath);
            throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
        }

        try {
            $usedNames = [];

            foreach ($assets as $asset) {
                $filename = $this->generateFilename($asset, $usedNames);

                if ($asset->isRemote()) {
                    $content = @file_get_contents($asset->getFilePath());

                    if (false === $content || false === $zipArchive->addFromString($filename, $content)) {
                        $zipArchive->close();
                        @unlink($zipPath);
                        throw new \RuntimeException('mautic.asset.asset.batch_download.error.unavailable');
                    }

                    continue;
                }

                $absolutePath = $asset->getAbsolutePath();

                if (empty($absolutePath) || false === $zipArchive->addFile($absolutePath, $filename)) {
                    $zipArchive->close();
                    @unlink($zipPath);
                    throw new \RuntimeException('mautic.asset.asset.batch_download.error.unavailable');
                }
            }

            if (true !== $zipArchive->close()) {
                @unlink($zipPath);
                throw new \RuntimeException('mautic.asset.asset.batch_download.error.zip_creation');
            }
        } catch (\Throwable $e) {
            if ($zipArchive->numFiles > 0) {
                $zipArchive->close();
            }
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            throw $e;
        }

        return $zipPath;
    }

    /**
     * @param array<string> $usedNames
     */
    private function generateFilename(Asset $asset, array &$usedNames): string
    {
        $originalFileName = $asset->getOriginalFileName();
        $filename         = $originalFileName ?: ($asset->getTitle() ?: (string) $asset->getId());

        $filename = InputHelper::transliterateFilename($filename);

        $index     = 1;
        $finalName = $filename;

        while (in_array(mb_strtolower($finalName), $usedNames, true)) {
            $pathInfo  = pathinfo($filename);
            $baseName  = $pathInfo['filename'] ?? $filename;
            $ext       = $pathInfo['extension'] ?? '';
            $candidate = $ext ? sprintf('%s (%d).%s', $baseName, $index, $ext) : sprintf('%s (%d)', $baseName, $index);
            $finalName = $candidate;
            ++$index;
        }

        $usedNames[] = mb_strtolower($finalName);

        return $finalName;
    }
}
