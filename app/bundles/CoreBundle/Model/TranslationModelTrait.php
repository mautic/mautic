<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides helper methods for determine the requested language from contact's profile and/or request
 *
 * Class TranslationModelTrait
 */
trait TranslationModelTrait
{
    /**
     * Get the entity based on requested translation
     *
     *
     * @param TranslationEntityInterface $entity
     * @param Lead                       $lead
     * @param Request|null               $request
     *
     * @return array[$parentEntity, TranslationEntityInterface $entity]
     */
    public function getTranslatedEntity(TranslationEntityInterface $entity, Lead $lead, Request $request = null)
    {
        $translationParent   = $entity->getTranslationParent();
        $translationChildren = $entity->getTranslationChildren()->toArray();

        if ($translationParent || count($translationChildren)) {
            if ($translationParent) {
                $translationChildren = $translationParent->getTranslationChildren();
            } else {
                $translationParent = $entity;
            }

            // Generate a list of translations
            $translations = [$translationParent->getLanguage()];
            foreach ($translationChildren as $c) {
                $translations[$c->getId()] = $c->getLanguage();
            }

            // Generate a list of translations for this entity
            $translationList = [];
            foreach ($translations as $id => $language) {
                $core = $this->getTranslationLocaleCore($language);
                if (!isset($languageList[$core])) {
                    $translationList[$core] = [];
                }
                $translationList[$core][$language] = $id;
            }

            // Get the contact's preferred language if defined
            $languageList = [];
            if ($leadPreference = $lead->getPreferredLocale()) {
                $languageList[$leadPreference] = $leadPreference;
            }

            // Check request for language
            if (null !== $request) {
                $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
                if (!empty($browserLanguages)) {
                    $browserLanguages = explode(',', $browserLanguages);
                    if (!empty($browserLanguages)) {
                        foreach ($browserLanguages as $language) {
                            if ($pos = strpos($language, ';q=') !== false) {
                                //remove weights
                                $language = substr($language, 0, ($pos + 1));
                            }
                            //change - to _
                            $language = str_replace('-', '_', $language);

                            if (!isset($languageList[$language])) {
                                $languageList[$language] = $language;
                            }
                        }
                    }
                }
            }

            $matchFound    = false;
            $preferredCore = false;
            foreach ($languageList as $language) {
                $core = $this->getTranslationLocaleCore($language);
                if (isset($translationList[$core])) {
                    // Does the dialect exist?
                    if (isset($translationList[$core][$language])) {
                        // We have a match
                        $matchFound = $translationList[$core][$language];

                        break;
                    } elseif (!$preferredCore) {
                        // This will be the fallback if no matches are found
                        $preferredCore = $core;
                    }
                }
            }

            if ($matchFound) {
                // A translation was found based on language preference
                $entity = ($matchFound == $translationParent->getId()) ? $translationParent : $translationChildren[$matchFound];
            } elseif ($preferredCore) {
                // Return the best matching language
                $bestMatch = array_values($translationList[$core])[0];
                $entity    = ($bestMatch == $translationParent->getId()) ? $translationParent : $translationChildren[$bestMatch];
            }
        }

        return [$translationParent, $entity];
    }

    /**
     * @param $locale
     *
     * @return string
     */
    private function getTranslationLocaleCore($locale)
    {
        if (strpos($locale, '_') !== false) {
            $locale = substr($locale, 0, 2);
        }

        return $locale;
    }
}