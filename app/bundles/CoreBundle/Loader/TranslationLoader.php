<?php

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationLoader extends ArrayLoader implements LoaderInterface
{
    public function __construct(
        private BundleHelper $bundleHelper,
        private PathsHelper $pathsHelper,
    ) {
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $bundles   = $this->bundleHelper->getMauticBundles(true);
        $catalogue = new MessageCatalogue($locale);

        // Bundle translations
        foreach ($bundles as $bundle) {
            // load translations
            $translations = $bundle['directory'].'/Translations/'.$locale;
            if (file_exists($translations)) {
                $iniFiles = new Finder();
                $iniFiles->files()->in($translations)->name('*.ini');

                foreach ($iniFiles as $file) {
                    $this->loadTranslations($catalogue, $locale, $file);
                }
            }
        }

        // Theme translations
        $themeDir = $this->pathsHelper->getSystemPath('current_theme', true);
        if (file_exists($themeTranslation = $themeDir.'/translations/'.$locale)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($themeTranslation)->name('*.ini');
            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        // 3rd Party translations
        $translationsDir = $this->pathsHelper->getSystemPath('translations', true).'/'.$locale;
        if (file_exists($translationsDir)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($translationsDir)->name('*.ini');

            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        // Overrides
        $overridesDir = $this->pathsHelper->getSystemPath('translations', true).'/overrides/'.$locale;
        if (file_exists($overridesDir)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($overridesDir)->name('*.ini');

            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        return $catalogue;
    }

    /**
     * Load the translation into the catalogue.
     *
     * @throws \Exception
     */
    private function loadTranslations($catalogue, $locale, $file): void
    {
        $iniFile  = $file->getRealpath();
        $content  = file_get_contents($iniFile);
        $messages = parse_ini_string($content, true);
        if (false === $messages) {
            // The translation file is corrupt
            if ('dev' === MAUTIC_ENV) {
                throw new \Exception($iniFile.' is corrupted');
            }

            return;
        }

        $domain        = substr($file->getFilename(), 0, -4);
        $thisCatalogue = parent::load($messages, $locale, $domain);
        $catalogue->addCatalogue($thisCatalogue);
    }
}
