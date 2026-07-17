<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Translation;

/**
 * This cannot be refactored to a decorator as we are changing a protected method. Let's hope it will get better in Symfony 8.
 */
class TranslatorLoader extends \Symfony\Bundle\FrameworkBundle\Translation\Translator
{
    protected function loadCatalogue(string $locale): void
    {
        // en_US is registered in TranslationLoaderPass, as language packs of other locales are only fetched at runtime
        if ('en_US' !== $locale) {
            $this->addResource('mautic', null, $locale, 'messages');
        }

        parent::loadCatalogue($locale);
    }
}
