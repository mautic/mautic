<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Class TranslationLoader.
 */
class TranslationLoader extends ArrayLoader implements LoaderInterface
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $bundles   = $this->factory->getMauticBundles(true);
        $catalogue = new MessageCatalogue($locale);

        //Bundle translations
        foreach ($bundles as $name => $bundle) {
            //load translations
            $translations = $bundle['directory'].'/Translations/'.$locale;
            if (file_exists($translations)) {
                $iniFiles = new Finder();
                $iniFiles->files()->in($translations)->name('*.ini');

                foreach ($iniFiles as $file) {
                    $this->loadTranslations($catalogue, $locale, $file);
                }
            }
        }

        //Theme translations
        $themeDir = $this->factory->getSystemPath('current_theme', true);
        if (file_exists($themeTranslation = $themeDir.'/translations/'.$locale)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($themeTranslation)->name('*.ini');
            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        //3rd Party translations
        $translationsDir = $this->factory->getSystemPath('translations', true).'/'.$locale;
        if (file_exists($translationsDir)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($translationsDir)->name('*.ini');

            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        //Overrides
        $overridesDir = $this->factory->getSystemPath('translations', true).'/overrides/'.$locale;
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
     * @param $catalogue
     * @param $locale
     * @param $file
     */
    private function loadTranslations($catalogue, $locale, $file)
    {
        $iniFile  = $file->getRealpath();
        $messages = parse_ini_file($iniFile, true);
        if (false === $messages) {
            // Likely a bad INI file; let's try encoding double quotes within double quotes then just ignore this file if it happens again
            $iniString = file_get_contents($iniFile);
            $quoteMap  = [
                "\xC2\x82"     => "'", // U+0082⇒U+201A single low-9 quotation mark
                "\xC2\x84"     => '"', // U+0084⇒U+201E double low-9 quotation mark
                "\xC2\x8B"     => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
                "\xC2\x91"     => "'", // U+0091⇒U+2018 left single quotation mark
                "\xC2\x92"     => "'", // U+0092⇒U+2019 right single quotation mark
                "\xC2\x93"     => '"', // U+0093⇒U+201C left double quotation mark
                "\xC2\x94"     => '"', // U+0094⇒U+201D right double quotation mark
                "\xC2\x9B"     => "'", // U+009B⇒U+203A single right-pointing angle quotation mark
                "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
                "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
                "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
                "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
                "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
                "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
                "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
                "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
                "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
                "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
                "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
                "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
            ];
            $search    = array_keys($quoteMap); // but: for efficiency you should
            $replace   = array_values($quoteMap); // pre-calculate these two arrays
            $iniString = str_replace($search, $replace, $iniString);

            if (preg_match_all('/^(.*?)="((.*?)?["]+(.*?)?)"$/m', $iniString, $matches)) {
                $search = $replace = [];
                foreach ($matches[2] as $hasQuotes) {
                    $search[]  = $hasQuotes;
                    $replace[] = str_replace('"', '&quot;', $hasQuotes);
                }
                $iniString = str_replace($search, $replace, $iniString);
            }
            $messages = parse_ini_string($iniString, true);

            if (false === $messages) {
                // prevent from crashing Mautic

                return;
            }
        }

        $domain        = substr($file->getFilename(), 0, -4);
        $thisCatalogue = parent::load($messages, $locale, $domain);
        $catalogue->addCatalogue($thisCatalogue);
    }
}
