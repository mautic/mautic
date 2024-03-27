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
            new TwigFunction('formFieldCleanInputAttributes', [$this, 'cleanInputAttributes']),
        ];
    }

    /**
     * Clean input evil attributes to prevent XSS
     * Remove any attribute starting with "on" or xmlns or javascript:. Used in href, src, value, data, etc.
     */
    public function cleanInputAttributes(string $value): string
    {
        // Remove any HTML tags
        $value = htmlspecialchars($value, ENT_SUBSTITUTE, 'UTF-8', false);
        // Remove any attribute starting with "on" or javascript used in href, src, value, data, etc.
        preg_match('/(on[A-Za-z]*\s*=|javascript:)/i', $value, $result);
        if (!empty($result)) {
            return '';
        }

        return $value;
    }
}
