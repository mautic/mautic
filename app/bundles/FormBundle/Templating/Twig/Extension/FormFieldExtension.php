<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Templating\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper as LeadFormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FormFieldExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('formFieldParseBooleanList', [FormFieldHelper::class, 'parseBooleanList']),
            new TwigFunction('formFieldParseList', [FormFieldHelper::class, 'parseList']),
            new TwigFunction('formFieldCountryChoices', [LeadFormFieldHelper::class, 'getCountryChoices']),
            new TwigFunction('formFieldRegionChoices', [LeadFormFieldHelper::class, 'getRegionChoices']),
            new TwigFunction('formFieldTimezonesChoices', [LeadFormFieldHelper::class, 'getTimezonesChoices']),
            new TwigFunction('formFieldLocaleChoices', [LeadFormFieldHelper::class, 'getLocaleChoices']),
        ];
    }
}
