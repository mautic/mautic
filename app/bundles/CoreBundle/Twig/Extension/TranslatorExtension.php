<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Translation\Translator;
use Twig\Attribute\AsTwigFunction;

final readonly class TranslatorExtension
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    #[AsTwigFunction(name: 'translatorGetJsLang')]
    public function getJsLang(): string
    {
        return $this->translator->getJsLang();
    }

    #[AsTwigFunction(name: 'translatorHasId')]
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
    #[AsTwigFunction(name: 'translatorConditional')]
    public function translatorConditional(string $preferred, string $alternative, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->transConditional($preferred, $alternative, $parameters, $domain, $locale);
    }

    #[AsTwigFunction(name: 'translatorGetHelper')]
    public function getHelper(): Translator
    {
        return $this->translator;
    }
}
