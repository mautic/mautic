<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\InputHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class InputExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('inputUrl', InputHelper::url(...)),
            new TwigFunction('inputAlphanum', InputHelper::alphanum(...)),
            new TwigFunction('inputTransliterate', InputHelper::transliterate(...)),
            new TwigFunction('inputClean', InputHelper::clean(...)),
        ];
    }
}
