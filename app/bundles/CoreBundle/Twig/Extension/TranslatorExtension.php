<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Translation\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TranslatorExtension extends AbstractExtension
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('translatorGetJsLang', [$this, 'getJsLang']),
            new TwigFunction('translatorHasId', [$this, 'translatorHasId']),
            new TwigFunction('translatorConditional', [$this, 'translatorConditional']),
            new TwigFunction('translatorGetHelper', [$this, 'getHelper']),
        ];
    }

    public function getJsLang(): string
    {
        return $this->translator->getJsLang();
    }

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
    public function translatorConditional(string $preferred, string $alternative, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->transConditional($preferred, $alternative, $parameters, $domain, $locale);
    }

    public function getHelper(): Translator
    {
        return $this->translator;
    }
}
