<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Translation\Translator;

class TranslatorExtension
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction('translatorGetJsLang')]
    public function getJsLang(): string
    {
        return $this->translator->getJsLang();
    }

    #[\Twig\Attribute\AsTwigFunction('translatorHasId')]
    public function translatorHasId(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        return $this->translator->hasId($id, $domain, $locale);
    }

    /**
     * Checks for $preferred string existence and returns translation if it
     * does.  Otherwise, returns translation for $alternative.
     *
     * @param array<mixed> $parameters
     */
    #[\Twig\Attribute\AsTwigFunction('translatorConditional')]
    public function translatorConditional(string $preferred, string $alternative, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->transConditional($preferred, $alternative, $parameters, $domain, $locale);
    }

    #[\Twig\Attribute\AsTwigFunction('translatorGetHelper')]
    public function getHelper(): Translator
    {
        return $this->translator;
    }
}
