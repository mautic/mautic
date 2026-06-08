<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FormFieldExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('leadFieldCountryChoices', [FormFieldHelper::class, 'getCountryChoices']),
            new TwigFunction('leadFieldRegionChoices', [FormFieldHelper::class, 'getRegionChoices']),
            new TwigFunction('leadFieldTimezonesChoices', [FormFieldHelper::class, 'getTimezonesChoices']),
            new TwigFunction('leadFieldLocaleChoices', [$this, 'getLeadFieldLocaleChoices']),
            new TwigFunction('leadFormFieldParseListForChoices', [FormFieldHelper::class, 'parseListForChoices']),
        ];
    }

    /**
     * Get locale choices with proper key=>value.
     *
     * @return array<string, string>
     */
    public function getLeadFieldLocaleChoices(): array
    {
        return array_flip(FormFieldHelper::getLocaleChoices());
    }
}
