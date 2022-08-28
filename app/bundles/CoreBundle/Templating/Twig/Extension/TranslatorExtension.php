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
        ];
    }

    public function getJsLang(): string
    {
        return $this->translatorHelper->getJsLang();
    }
}
