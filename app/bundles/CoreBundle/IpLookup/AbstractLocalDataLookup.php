<?php

namespace Mautic\CoreBundle\IpLookup;

use GuzzleHttp\RequestOptions;
use Mautic\CoreBundle\Form\Type\IpLookupDownloadDataStoreButtonType;
use Psr\Http\Client\ClientExceptionInterface;

abstract class AbstractLocalDataLookup extends AbstractLookup implements IpLookupFormInterface
{
    /**
     * @var string
     */
    public const TAR_CACHE_FOLDER = 'unpack';

    /**
     * @var string
     */
    public const TAR_TEMP_FILE = 'temp.tar.gz';

    /**
     * Path to the local data store.
     *
     * @return string
     */
    abstract public function getLocalDataStoreFilepath();

    /**
     * Return the URL to manually download.
     */
    abstract public function getRemoteDateStoreDownloadUrl(): string;

    /**
     * @return string
     */
    public function getConfigFormService()
    {
        return IpLookupDownloadDataStoreButtonType::class;
    }

    /**
     * @return array
     */
    public function getConfigFormThemes()
    {
        return [];
    }

    /**
     * Used by the mautic:iplookup:update_data command and form fetch button (if applicable) to update local IP data stores.
     */
    public function downloadRemoteDataStore(): bool
    {
        $package   = $this->getRemoteDateStoreDownloadUrl();

        if (empty($package)) {
            $this->logger->error('Failed to fetch remote IP data: Invalid or inactive MaxMind license key');

            return false;
        }
        try {
            $data = $this->client->get($package, [
                RequestOptions::ALLOW_REDIRECTS => true,
            ]);
        } catch (ClientExceptionInterface $exception) {
            $this->logger->error('Failed to fetch remote IP data: '.$exception->getMessage());

            return false;
        }

        $tempTarget        = $this->cacheDir.'/'.basename($package);
        $tempExt           = strtolower(pathinfo($package, PATHINFO_EXTENSION));
        $localTarget       = $this->getLocalDataStoreFilepath();
        $localTargetExt    = strtolower(pathinfo($localTarget, PATHINFO_EXTENSION));

        try {
            $success = false;

            switch (true) {
                case $localTargetExt === $tempExt:
                    $success = (bool) file_put_contents($localTarget, $data->getBody());

                    break;

                case $this->endsWith($package, 'tar.gz'):
                    /**
                     * If tar.gz it loops whole folder structure and copy the file which has the same basename as
                     * desired localTarget.
                     */
                    $tempTargetFolder = $this->cacheDir.'/'.self::TAR_CACHE_FOLDER;
                    $temporaryPhar    = $tempTargetFolder.'/'.self::TAR_TEMP_FILE;
                    if (!is_dir($tempTargetFolder)) {
                        // dir doesn't exist, make it
                        mkdir($tempTargetFolder);
                    }
                    file_put_contents($temporaryPhar, $data->getBody());
                    $pharData = new \PharData($temporaryPhar);
                    foreach (new \RecursiveIteratorIterator($pharData) as $file) {
                        /** @var \PharFileInfo $file */
                        if ($file->getBasename() === basename($localTarget)) {
                            $success = copy($file->getPathname(), $localTarget);
                        }
                    }
                    @unlink($temporaryPhar);

                    break;

                case 'gz' === $tempExt:
                    $memLimit = $this->sizeInByte(ini_get('memory_limit'));
                    $freeMem  = $memLimit - memory_get_peak_usage();
                    // check whether there is enough memory to handle large iplookp DB
                    // or will throw iplookup exception
                    if (function_exists('gzdecode') && strlen($data->getBody()) < ($freeMem / 3)) {
                        $success = (bool) file_put_contents($localTarget, gzdecode($data->getBody()));
                    } elseif (function_exists('gzopen')) {
                        if (file_put_contents($tempTarget, $data->getBody())) {
                            $bufferSize = 4096; // read 4kb at a time
                            $file       = gzopen($tempTarget, 'rb');
                            $outFile    = fopen($localTarget, 'wb');
                            while (!gzeof($file)) {
                                fwrite($outFile, gzread($file, $bufferSize));
                            }
                            fclose($outFile);
                            gzclose($file);
                            @unlink($tempTarget);
                            $success = true;
                        }
                    }

                    break;

                case 'zip' === $tempExt:
                    file_put_contents($tempTarget, $data->getBody());

                    if ('' !== $localTargetExt) {
                        $localTarget = dirname($localTarget);
                    }

                    $zipper = new \ZipArchive();

                    $zipper->open($tempTarget);
                    $success = $zipper->extractTo($localTarget);
                    $zipper->close();
                    @unlink($tempTarget);
                    break;
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to fetch remote IP data: '.$exception->getMessage());

            return false;
        }

        return $success;
    }

    public static function cleanUrl(string $url): string
    {
        $urlParts = parse_url($url);

        if (!is_array($urlParts) || !isset($urlParts['scheme'], $urlParts['host'])) {
            return $url;
        }

        // Skip user:pass authentication.
        $url = $urlParts['scheme'].'://'.$urlParts['host'];

        if (isset($urlParts['port'])) {
            $url .= ':'.$urlParts['port'];
        }

        if (isset($urlParts['path'])) {
            $url .= $urlParts['path'];
        }

        if (isset($urlParts['query'])) {
            $query = [];
            parse_str($urlParts['query'], $query);

            foreach (['user', 'username', 'pwd', 'pass', 'password'] as $sensitiveField) {
                if (isset($query[$sensitiveField])) {
                    unset($query[$sensitiveField]);
                }
            }

            $url .= '?'.http_build_query($query);
        }

        if (!isset($urlParts['fragment'])) {
            return $url;
        }

        return $url.('#'.$urlParts['fragment']);
    }

    /**
     * Get the common directory for data.
     *
     * @return string|null
     */
    protected function getDataDir()
    {
        if (null !== $this->cacheDir) {
            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir);
            }

            $dataDir = $this->cacheDir.'/../ip_data';

            if (!file_exists($dataDir)) {
                mkdir($dataDir);
            }

            return $dataDir;
        }

        return null;
    }

    protected function sizeInByte($size)
    {
        $data = (int) substr($size, 0, -1);

        return match (strtoupper(substr($size, -1))) {
            'K' => $data * 1024,
            'M' => $data * 1024 * 1024,
            'G' => $data * 1024 * 1024 * 1024,
            default => null,
        };
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }
}
