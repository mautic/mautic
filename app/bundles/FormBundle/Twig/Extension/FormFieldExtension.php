<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FormFieldExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('formFieldParseBooleanList', [FormFieldHelper::class, 'parseBooleanList']),
            new TwigFunction('formFieldParseList', [FormFieldHelper::class, 'parseList']),
            new TwigFunction('formFieldParseListForChoices', [FormFieldHelper::class, 'parseListForChoices']),
        ];
    }
}
