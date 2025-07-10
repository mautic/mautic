<?php

namespace Mautic\CoreBundle\Helper;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\Language\Installer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper class for managing Mautic's installed languages.
 */
class LanguageHelper
{
    private string $cacheFile;

    private Installer $installer;

    private array $supportedLanguages = [];

    private string $installedTranslationsDirectory;

    private string $defaultTranslationsDirectory;

    public function __construct(
        private PathsHelper $pathsHelper,
        private LoggerInterface $logger,
        private CoreParametersHelper $coreParametersHelper,
        private Client $client,
        private TranslatorInterface $translator,
    ) {
        $this->defaultTranslationsDirectory   = __DIR__.'/../Translations';
        $this->installedTranslationsDirectory = $this->pathsHelper->getSystemPath('translations_root').'/translations';
        $this->installer                      = new Installer($this->installedTranslationsDirectory);

        // Moved to outside environment folder so that it doesn't get wiped on each config update
        $this->cacheFile = $pathsHelper->getSystemPath('cache').'/../languageList.txt';
    }

    /**
     * @return array<string>
     */
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
     */
    public function extractLanguagePackage($languageCode): array
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
            $error = match ($archive) {
                \ZipArchive::ER_EXISTS => 'mautic.core.update.archive_file_exists',
                \ZipArchive::ER_INCONS, \ZipArchive::ER_INVAL, \ZipArchive::ER_MEMORY => 'mautic.core.update.archive_zip_corrupt',
                \ZipArchive::ER_NOENT => 'mautic.core.update.archive_no_such_file',
                \ZipArchive::ER_NOZIP => 'mautic.core.update.archive_not_valid_zip',
                default               => 'mautic.core.update.archive_could_not_open',
            };

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
            $this->logger->error('An error occurred while attempting to fetch the language list: '.$exception->getMessage());

            return (!$returnError)
                ? []
                : [
                    'error'   => true,
                    'message' => 'mautic.core.language.helper.error.fetching.languages',
                ];
        }

        if (200 != $data->getStatusCode()) {
            // Log the error
            $this->logger->error(
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
     */
    public function fetchPackage($languageCode): array
    {
        // Check if we have a cache file, generate it if not
        if (!is_readable($this->cacheFile)) {
            $this->fetchLanguages();
        }

        if (!is_readable($this->cacheFile)) {
            return [
                'error'   => true,
                'message' => 'mautic.core.language.helper.error.fetching.languages',
            ];
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
            $this->logger->error('An error occurred while attempting to fetch the package: '.$exception->getMessage());

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

    /**
     * Returns Mautic translation files.
     *
     * @param string[] $forBundles empty array means all bundles
     *
     * @return array<string,string[]>
     */
    public function getLanguageFiles(array $forBundles = []): array
    {
        $files         = [];
        $mauticBundles = $this->coreParametersHelper->get('bundles');
        $pluginBundles = $this->coreParametersHelper->get('plugin.bundles');

        foreach (array_merge($mauticBundles, $pluginBundles) as $bundle) {
            // Apply the bundle filter.
            if (!empty($forBundles) && !in_array($bundle['bundle'], $forBundles)) {
                continue;
            }

            // Parse the namespace into a filepath
            $translationsDir = $bundle['directory'].'/Translations/en_US';

            if (is_dir($translationsDir)) {
                $files[$bundle['bundle']] = [];

                // Get files within the directory
                $finder = new Finder();
                $finder->files()->in($translationsDir)->name('*.ini');

                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($finder as $file) {
                    $files[$bundle['bundle']][] = $file->getPathname();
                }

                asort($files[$bundle['bundle']]);
            }
        }

        return $files;
    }

    public function createLanguageFile(string $filePath, string $content): void
    {
        $bundleDir   = dirname($filePath, 1);
        $languageDir = dirname($filePath, 2);

        foreach ([$languageDir, $bundleDir] as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (!mkdir($dir)) {
                throw new \RuntimeException($this->translator->trans('mautic.core.command.transifex_error_creating_directory', ['%directory%' => $dir]));
            }
        }

        if (!file_put_contents($filePath, $content)) {
            throw new \RuntimeException($this->translator->trans('mautic.core.command.transifex_error_creating_file', ['%file%' => $filePath]));
        }
    }

    private function loadSupportedLanguages(): void
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

    /**
     * @return array<string>
     */
    public function getLanguageChoices(): array
    {
        // Get the list of available languages
        $languages   = $this->fetchLanguages(false, false);
        $choices     = [];

        foreach ($languages as $code => $langData) {
            $choices[$langData['name']] = $code;
        }

        $choices = array_merge($choices, array_flip($this->getSupportedLanguages()));

        // Alpha sort the languages by name
        ksort($choices, SORT_FLAG_CASE | SORT_NATURAL);

        return $choices;
    }
}
