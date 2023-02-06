<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\InputHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InputExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('inputUrl', [InputHelper::class, 'url']),
            new TwigFunction('inputAlphanum', [InputHelper::class, 'alphanum']),
            new TwigFunction('inputTransliterate', [InputHelper::class, 'transliterate']),
            new TwigFunction('inputClean', [InputHelper::class, 'clean']),
        ];
    }
}
