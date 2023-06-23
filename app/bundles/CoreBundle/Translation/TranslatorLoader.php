<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Translation;

class TranslatorLoader extends \Symfony\Bundle\FrameworkBundle\Translation\Translator
{
    protected function loadCatalogue(string $locale): void
    {
        if ('en_US' !== $locale) {
            // Always force en_US so that it's available for fallback
            $this->addResource('mautic', null, 'en_US', 'messages');
        }

        $this->addResource('mautic', null, $locale, 'messages');

        parent::loadCatalogue($locale);
    }
}
