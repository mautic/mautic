<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

class ImportHelper
{
    public function __construct(
        private PathsHelper $pathsHelper,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \RuntimeException If the ZIP file cannot be opened or JSON is invalid
     */
    public function readZipFile(string $filePath): array
    {
        $tempDir      = sys_get_temp_dir();
        $zip          = new \ZipArchive();
        $jsonFilePath = null;

        if (true !== $zip->open($filePath)) {
            throw new \RuntimeException(sprintf('Unable to open ZIP file: %s', $filePath));
        }

        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            throw new \RuntimeException(sprintf('Unable to extract ZIP file to temp directory: %s', $tempDir));
        }

        $mediaPath = $this->pathsHelper->getSystemPath('media').'/files/';

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename        = $zip->getNameIndex($i);
            $sourcePath      = $tempDir.'/'.$filename;
            $destinationPath = $mediaPath.substr($filename, strlen('assets/'));

            if (str_starts_with($filename, 'assets/')) {
                if (is_dir($sourcePath)) {
                    if (!is_dir($destinationPath) && !mkdir($destinationPath, 0755, true) && !is_dir($destinationPath)) {
                        throw new \RuntimeException(sprintf('Failed to create directory: %s', $destinationPath));
                    }
                } else {
                    $dirPath = dirname($destinationPath);
                    if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true) && !is_dir($dirPath)) {
                        throw new \RuntimeException(sprintf('Failed to create directory: %s', $dirPath));
                    }
                    if (!copy($sourcePath, $destinationPath)) {
                        throw new \RuntimeException(sprintf('Failed to copy file to destination: %s', $destinationPath));
                    }
                }
            } elseif ('json' === pathinfo($filename, PATHINFO_EXTENSION)) {
                $jsonFilePath = $tempDir.'/'.$filename;
            }
        }

        $zip->close();

        if (!$jsonFilePath || !is_readable($jsonFilePath)) {
            throw new \RuntimeException('JSON file not found or not readable in ZIP archive.');
        }

        $fileContents = file_get_contents($jsonFilePath);
        if (false === $fileContents) {
            throw new \RuntimeException('Failed to read JSON file contents.');
        }

        $jsonData = json_decode($fileContents, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        return $jsonData;
    }

    /**
     * @param array<string, string|array<mixed, mixed>> &$input
     */
    public function recursiveRemoveEmailaddress(array &$input): void
    {
        foreach ($input as &$value) {
            if (is_string($value)) {
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $value = '';
                } else {
                    $value = preg_replace(
                        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
                        '',
                        $value
                    );
                }
            } elseif (is_array($value)) {
                $this->recursiveRemoveEmailaddress($value);
            }
        }
    }
}
