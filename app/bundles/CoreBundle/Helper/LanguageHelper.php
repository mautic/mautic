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

use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Helper class for managing Mautic's installed languages.
 */
class LanguageHelper
{
    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var \Joomla\Http\Http
     */
    private $connector;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;

        // Moved to outside environment folder so that it doesn't get wiped on each config update
        $this->cacheFile = $this->factory->getSystemPath('cache').'/../languageList.txt';
        $this->connector = HttpFactory::getHttp();
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
        $packagePath = $this->factory->getSystemPath('cache').'/'.$languageCode.'.zip';
        $translator  = $this->factory->getTranslator();

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

        if ($archive !== true) {
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
        $zipper->extractTo($this->factory->getSystemPath('translations_root').'/translations');
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
        $overrideFile = $this->factory->getParameter('language_list_file');
        if (!empty($overrideFile) && is_readable($overrideFile)) {
            $overrideData = json_decode(file_get_contents($overrideFile), true);
            if (isset($overrideData['languages'])) {
                return $overrideData['languages'];
            } elseif (isset($overrideData['name'])) {
                return $overrideData;
            } else {
                return [];
            }
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
            $data      = $this->connector->post('https://updates.mautic.org/index.php?option=com_mauticdownload&task=fetchLanguages', [], [], 10);
            $languages = json_decode($data->body, true);
            $languages = $languages['languages'];
        } catch (\Exception $exception) {
            // Log the error
            $logger = $this->factory->getLogger();
            $logger->addError('An error occurred while attempting to fetch the language list: '.$exception->getMessage());

            return (!$returnError) ? [] : [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.fetching.languages',
            ];
        }

        if ($data->code != 200) {
            // Log the error
            $logger = $this->factory->getLogger();
            $logger->addError(sprintf(
                'An unexpected %1$s code was returned while attempting to fetch the language.  The message received was: %2$s',
                $data->code,
                is_string($data->body) ? $data->body : implode('; ', $data->body)
            ));

            return (!$returnError) ? [] : [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.fetching.languages',
            ];
        }

        // Alphabetize the languages
        ksort($languages);

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

        $langUrl = 'https://updates.mautic.org/index.php?option=com_mauticdownload&task=downloadLanguagePackage&langCode='.$languageCode;

        // GET the update data
        try {
            $data = $this->connector->get($langUrl);
        } catch (\Exception $exception) {
            $logger = $this->factory->getLogger();
            $logger->addError('An error occurred while attempting to fetch the package: '.$exception->getMessage());

            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.fetching.package.exception',
                'vars'    => [
                    '%exception%' => $exception->getMessage(),
                ],
            ];
        }

        if ($data->code >= 300 && $data->code < 400) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.follow.redirects',
                'vars'    => [
                    '%url%' => $langUrl,
                ],
            ];
        } elseif ($data->code != 200) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.on.language.server.side',
                'vars'    => [
                    '%code%' => $data->code,
                ],
            ];
        }

        // Set the filesystem target
        $target = $this->factory->getSystemPath('cache').'/'.$languageCode.'.zip';

        // Write the response to the filesystem
        file_put_contents($target, $data->body);

        // Return an array for the sake of consistency
        return [
            'error' => false,
        ];
    }
}
