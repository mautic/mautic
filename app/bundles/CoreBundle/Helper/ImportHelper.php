<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class ImportHelper
{
    public function __construct(
        private PathsHelper $pathsHelper,
    ) {
    }

    /**
     * @return ?array<string, mixed>
     */
    public function readZipFile(string $filePath): ?array
    {
        $tempDir      = sys_get_temp_dir();
        $zip          = new \ZipArchive();
        $jsonFilePath = null;

        $result = $zip->open($filePath);
        if (true === $result) {
            $zip->extractTo($tempDir);
            $mediaPath    = $this->pathsHelper->getSystemPath('media').'/files/';

            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $filename        = $zip->getNameIndex($i);
                $sourcePath      = $tempDir.'/'.$filename;
                $destinationPath = $mediaPath.substr($filename, strlen('assets/'));

                if (str_starts_with($filename, 'assets/')) {
                    if (is_dir($sourcePath)) {
                        if (!is_dir($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }
                    } else {
                        $dirPath = dirname($destinationPath);
                        if (!is_dir($dirPath)) {
                            mkdir($dirPath, 0755, true);
                        }
                        copy($sourcePath, $destinationPath);
                    }
                } elseif ('json' === pathinfo($filename, PATHINFO_EXTENSION)) {
                    $jsonFilePath = $tempDir.'/'.$filename;
                }
            }

            $zip->close();
        }

        if ($jsonFilePath) {
            $fileContents = file_get_contents($jsonFilePath);

            return json_decode($fileContents, true);
        } else {
            return $jsonFilePath;
        }
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
