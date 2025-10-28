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

        // Zip Bomb protection constants
        $maxFiles     = 1000;  // Maximum number of files
        $maxSize      = 100 * 1024 * 1024;  // 100MB total uncompressed size
        $maxRatio     = 10;    // Maximum compression ratio (1:10)
        $readLength   = 1024;  // Read buffer size

        if (true !== $zip->open($filePath)) {
            throw new \RuntimeException(sprintf('Unable to open ZIP file: %s', $filePath));
        }

        $fileCount   = 0;
        $totalSize   = 0;
        $realTempDir = rtrim(realpath($tempDir), '/');

        // Store file information before closing the ZIP
        $fileList = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            if (!empty($filename)) {
                $fileList[] = $filename;
            }
        }

        // Validate all files before extraction
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $stat     = $zip->statIndex($i);

            // Skip empty filenames
            if (empty($filename)) {
                continue;
            }

            // Check file count limit
            if (!str_ends_with($filename, '/')) {
                ++$fileCount;
                if ($fileCount > $maxFiles) {
                    $zip->close();
                    throw new \RuntimeException('ZIP file contains too many files.');
                }
            }

            // Check path traversal
            $normalizedFilename = $this->normalizePath($filename);
            if (str_contains($normalizedFilename, '..') || str_starts_with($normalizedFilename, '/')) {
                $zip->close();
                throw new \RuntimeException('Unsafe file path detected in ZIP: '.$filename);
            }

            // Check compression ratio for potential zip bomb
            if (isset($stat['size']) && isset($stat['comp_size']) && $stat['comp_size'] > 0) {
                $ratio = $stat['size'] / $stat['comp_size'];
                if ($ratio > $maxRatio) {
                    $zip->close();
                    throw new \RuntimeException('Suspicious compression ratio detected in ZIP file.');
                }
            }

            // For files in subdirectories, ensure they don't escape the temp directory
            if (str_contains($normalizedFilename, '/')) {
                $extractionPath           = $tempDir.'/'.$normalizedFilename;
                $normalizedExtractionPath = $this->normalizePath($extractionPath);

                if (!str_starts_with($normalizedExtractionPath, $realTempDir)) {
                    $zip->close();
                    throw new \RuntimeException('Unsafe file path detected in ZIP: '.$filename);
                }
            }
        }

        // Extract files using streaming to prevent zip bomb
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            $stat     = $zip->statIndex($i);

            if (empty($filename)) {
                continue;
            }

            $sourcePath = $tempDir.'/'.$filename;

            if (!str_ends_with($filename, '/')) {
                // Create directory if needed
                $dirPath = dirname($sourcePath);
                if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true) && !is_dir($dirPath)) {
                    $zip->close();
                    throw new \RuntimeException(sprintf('Failed to create directory: %s', $dirPath));
                }

                // Stream extract file to prevent zip bomb
                $fp = $zip->getStream($filename);
                if (!$fp) {
                    $zip->close();
                    throw new \RuntimeException('Failed to open file stream from ZIP.');
                }

                $currentSize = 0;
                $fileHandle  = fopen($sourcePath, 'wb');
                if (!$fileHandle) {
                    fclose($fp);
                    $zip->close();
                    throw new \RuntimeException('Failed to create file: '.$sourcePath);
                }

                while (!feof($fp)) {
                    $data = fread($fp, $readLength);
                    if (false === $data) {
                        break;
                    }

                    $currentSize += strlen($data);
                    $totalSize += strlen($data);

                    // Check total size limit
                    if ($totalSize > $maxSize) {
                        fclose($fileHandle);
                        fclose($fp);
                        $zip->close();
                        throw new \RuntimeException('Uncompressed ZIP contents exceed allowed size.');
                    }

                    // Check compression ratio during extraction
                    if (isset($stat['comp_size']) && $stat['comp_size'] > 0) {
                        $ratio = $currentSize / $stat['comp_size'];
                        if ($ratio > $maxRatio) {
                            fclose($fileHandle);
                            fclose($fp);
                            $zip->close();
                            throw new \RuntimeException('Suspicious compression ratio detected during extraction.');
                        }
                    }

                    fwrite($fileHandle, $data);
                }

                fclose($fileHandle);
                fclose($fp);
            } else {
                // Create directory
                if (!is_dir($sourcePath) && !mkdir($sourcePath, 0755, true) && !is_dir($sourcePath)) {
                    $zip->close();
                    throw new \RuntimeException(sprintf('Failed to create directory: %s', $sourcePath));
                }
            }
        }

        $zip->close();

        $mediaPath = $this->pathsHelper->getSystemPath('media').'/files/';

        // Process extracted files using stored file list
        foreach ($fileList as $filename) {
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

        if (!$jsonFilePath || !is_readable($jsonFilePath)) {
            throw new \RuntimeException('JSON file not found or not readable in ZIP archive.');
        }

        $fileContents = file_get_contents($jsonFilePath);
        if (false === $fileContents) {
            throw new \RuntimeException('Failed to read JSON file contents.');
        }

        $jsonData = json_decode($fileContents, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            unlink($jsonFilePath);
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        return $jsonData;
    }

    private function normalizePath(string $path): string
    {
        $parts    = [];
        $segments = explode('/', str_replace('\\', '/', $path));

        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                if (!empty($parts)) {
                    array_pop($parts);
                }
            } else {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
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
