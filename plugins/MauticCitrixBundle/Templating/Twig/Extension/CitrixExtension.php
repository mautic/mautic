<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCitrixBundle\Templating\Twig\Extension;

use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CitrixExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('citrix_choices', [CitrixHelper::class, 'getCitrixChoices']),
        ];
    }
}
