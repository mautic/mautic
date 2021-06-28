<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\Language\Installer;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

/**
 * Helper class for managing Mautic's installed languages.
 */
class LanguageHelper
{
    private string $cacheFile;
    private Client $client;
    private PathsHelper $pathsHelper;
    private Logger $logger;
    private Installer $installer;
    private CoreParametersHelper $coreParametersHelper;
    private array $supportedLanguages = [];
    private string $installedTranslationsDirectory;
    private string $defaultTranslationsDirectory;

    public function __construct(PathsHelper $pathsHelper, Logger $logger, CoreParametersHelper $coreParametersHelper, Client $client)
    {
        $this->pathsHelper                    = $pathsHelper;
        $this->logger                         = $logger;
        $this->coreParametersHelper           = $coreParametersHelper;
        $this->client                         = $client;
        $this->defaultTranslationsDirectory   = __DIR__.'/../Translations';
        $this->installedTranslationsDirectory = $this->pathsHelper->getSystemPath('translations_root').'/translations';
        $this->installer                      = new Installer($this->installedTranslationsDirectory);

        // Moved to outside environment folder so that it doesn't get wiped on each config update
        $this->cacheFile = $pathsHelper->getSystemPath('cache').'/../languageList.txt';
    }

    public function getSupportedLanguages(): array
    {
        if (!empty($this->supportedLanguages)) {
            return $this->supportedLanguages;
        }

        $this->loadSupportedLanguages();

        return $this->supportedLanguages;
    }

    /**
     * Extracts a downloaded package for the specified language.
     *
     * This will attempt to download the package if it is not found
     *
     * @param $languageCode
     *
     * @return array
     */
    public function extractLanguagePackage($languageCode)
    {
        $packagePath = $this->pathsHelper->getSystemPath('cache').'/'.$languageCode.'.zip';

        // Make sure the package actually exists
        if (!file_exists($packagePath)) {
            // Let's try to fetch it
            $result = $this->fetchPackage($languageCode);

            // If there was a failure, there's nothing else we can do here
            if ($result['error']) {
                return $result;
            }
        }

        $zipper  = new \ZipArchive();
        $archive = $zipper->open($packagePath);

        if (true !== $archive) {
            // Get the exact error
            switch ($archive) {
                case \ZipArchive::ER_EXISTS:
                    $error = 'mautic.core.update.archive_file_exists';
                    break;
                case \ZipArchive::ER_INCONS:
                case \ZipArchive::ER_INVAL:
                case \ZipArchive::ER_MEMORY:
                    $error = 'mautic.core.update.archive_zip_corrupt';
                    break;
                case \ZipArchive::ER_NOENT:
                    $error = 'mautic.core.update.archive_no_such_file';
                    break;
                case \ZipArchive::ER_NOZIP:
                    $error = 'mautic.core.update.archive_not_valid_zip';
                    break;
                case \ZipArchive::ER_READ:
                case \ZipArchive::ER_SEEK:
                case \ZipArchive::ER_OPEN:
                default:
                    $error = 'mautic.core.update.archive_could_not_open';
                    break;
            }

            return [
                'error'   => true,
                'message' => $error,
            ];
        }

        // Extract the archive file now
        $tempDir = $this->pathsHelper->getSystemPath('tmp');

        if (!$zipper->extractTo($tempDir)) {
            return [
                'error'   => true,
                'message' => 'mautic.core.update.archive_failed_to_extract',
            ];
        }

        $this->installer->install($tempDir, $languageCode)
            ->cleanup();

        $zipper->close();

        // We can remove the package now
        @unlink($packagePath);

        return [
            'error'   => false,
            'message' => 'mautic.core.language.helper.language.saved.successfully',
        ];
    }

    /**
     * Fetches the list of available languages.
     *
     * @param bool $overrideCache
     *
     * @return array
     */
    public function fetchLanguages($overrideCache = false, $returnError = true)
    {
        $overrideFile = $this->coreParametersHelper->get('language_list_file');
        if (!empty($overrideFile) && is_readable($overrideFile)) {
            $overrideData = json_decode(file_get_contents($overrideFile), true);
            if (isset($overrideData['languages'])) {
                return $overrideData['languages'];
            } elseif (isset($overrideData['name'])) {
                return $overrideData;
            }

            return [];
        }

        // Check if we have a cache file and try to return cached data if so
        if (!$overrideCache && is_readable($this->cacheFile)) {
            $cacheData = json_decode(file_get_contents($this->cacheFile), true);

            // If we're within the cache time, return the cached data
            if ($cacheData['checkedTime'] > strtotime('-12 hours')) {
                return $cacheData['languages'];
            }
        }

        // Get the language data
        try {
            $data = $this->client->get(
                $this->coreParametersHelper->get('translations_list_url'),
                [\GuzzleHttp\RequestOptions::TIMEOUT => 10]
            );
            $manifest  = json_decode($data->getBody(), true);
            $languages = [];

            // translate the manifest (plain array) to a format
            // expected everywhere else inside mautic (locale keyed sorted array)
            foreach ($manifest['languages'] as $lang) {
                $languages[$lang['locale']] = $lang;
            }
            ksort($languages);
        } catch (\Exception $exception) {
            // Log the error
            $this->logger->addError('An error occurred while attempting to fetch the language list: '.$exception->getMessage());

            return (!$returnError)
                ? []
                : [
                    'error'   => true,
                    'message' => 'mautic.core.language.helper.error.fetching.languages',
                ];
        }

        if (200 != $data->getStatusCode()) {
            // Log the error
            $this->logger->addError(
                sprintf(
                    'An unexpected %1$s code was returned while attempting to fetch the language.  The message received was: %2$s',
                    $data->code,
                    (string) $data->getBody()
                )
            );

            return (!$returnError)
                ? []
                : [
                    'error'   => true,
                    'message' => 'mautic.core.language.helper.error.fetching.languages',
                ];
        }

        // Store to cache
        $cacheData = [
            'checkedTime' => time(),
            'languages'   => $languages,
        ];

        file_put_contents($this->cacheFile, json_encode($cacheData));

        return $languages;
    }

    /**
     * Fetches a language package from the remote server.
     *
     * @param string $languageCode
     *
     * @return array
     */
    public function fetchPackage($languageCode)
    {
        // Check if we have a cache file, generate it if not
        if (!is_readable($this->cacheFile)) {
            $this->fetchLanguages();
        }

        $cacheData = json_decode(file_get_contents($this->cacheFile), true);

        // Make sure the language actually exists
        if (!isset($cacheData['languages'][$languageCode])) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.invalid.language',
                'vars'    => [
                    '%language%' => $languageCode,
                ],
            ];
        }

        $langUrl = $this->coreParametersHelper->get('translations_fetch_url').$languageCode.'.zip';

        // GET the update data
        try {
            $data = $this->client->get($langUrl);
        } catch (\Exception $exception) {
            $this->logger->addError('An error occurred while attempting to fetch the package: '.$exception->getMessage());

            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.fetching.package.exception',
                'vars'    => [
                    '%exception%' => $exception->getMessage(),
                ],
            ];
        }

        if ($data->getStatusCode() >= 300 && $data->getStatusCode() < 400) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.follow.redirects',
                'vars'    => [
                    '%url%' => $langUrl,
                ],
            ];
        } elseif (200 != $data->getStatusCode()) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.on.language.server.side',
                'vars'    => [
                    '%code%' => $data->getStatusCode(),
                ],
            ];
        }

        // Set the filesystem target
        $target = $this->pathsHelper->getSystemPath('cache').'/'.$languageCode.'.zip';

        // Write the response to the filesystem
        file_put_contents($target, $data->getBody());

        // Return an array for the sake of consistency
        return [
            'error' => false,
        ];
    }

    private function loadSupportedLanguages()
    {
        // Find available translations
        $finder = new Finder();
        $finder
            ->directories()
            ->in($this->defaultTranslationsDirectory)
            ->in($this->installedTranslationsDirectory)
            ->ignoreDotFiles(true)
            ->depth('== 0');

        foreach ($finder as $dir) {
            $locale = $dir->getFilename();

            // Check config exists
            $configFile = $dir->getRealpath().'/config.json';
            if (!file_exists($configFile)) {
                return;
            }

            $config                            = json_decode(file_get_contents($configFile), true);
            $this->supportedLanguages[$locale] = (!empty($config['name'])) ? $config['name'] : $locale;
        }
    }
}
