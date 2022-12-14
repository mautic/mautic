<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\TranslatorHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TranslatorExtension extends AbstractExtension
{
    private TranslatorHelper $translatorHelper;

    public function __construct(TranslatorHelper $translatorHelper)
    {
        $this->translatorHelper = $translatorHelper;
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
        return $this->translatorHelper->getJsLang();
    }

    public function translatorHasId(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        return $this->translatorHelper->hasId($id, $domain, $locale);
    }

    public function translatorConditional($preferred, $alternative, $parameters = [], $domain = null, $locale = null)
    {
        return $this->translatorHelper->transConditional($preferred, $alternative, $parameters, $domain, $locale);
    }

    public function getHelper(): TranslatorHelper
    {
        return $this->translatorHelper;
    }
}
