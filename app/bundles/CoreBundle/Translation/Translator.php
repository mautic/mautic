<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;

/**
 * Extended Translator service.
 */
class Translator extends BaseTranslator
{
    /**
     * Check if the specified message ID exists.
     *
     * @param string      $id     The message id (may also be an object that can be cast to string)
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return bool true if the message has a translation, false otherwise
     */
    public function hasId($id, $domain = null, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        return $this->getCatalogue($locale)->has((string) $id, $domain);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCatalogue($locale)
    {
        if ($locale != 'en_US') {
            // Always force en_US so that it's available for fallback
            $this->addResource('mautic', null, 'en_US', 'messages');
        }

        $this->addResource('mautic', null, $locale, 'messages');

        parent::loadCatalogue($locale);
    }

    /**
     * Checks for $preferred string existence and returns translation if it does.  Otherwise, returns translation for
     * $alternative.
     *
     * @param      $preferred
     * @param      $alternative
     * @param      $parameters
     * @param null $domain
     * @param null $locale
     *
     * @return string
     */
    public function transConditional($preferred, $alternative, $parameters = [], $domain = null, $locale = null)
    {
        if ($this->hasId($preferred, $domain, $locale)) {
            return $this->trans($preferred, $parameters, $domain, $locale);
        } else {
            return $this->trans($alternative, $parameters, $domain, $locale);
        }
    }
}
