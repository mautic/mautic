<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatterExtension extends AbstractExtension
{
    public function __construct(
        protected FormatterHelper $formatterHelper
    ) {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('formatter_simple_array_to_html', [$this, 'simpleArrayToHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('format', [$this, '_'], ['is_safe' => ['all']]),
            new TwigFunction('normalizeStringValue', [$this, 'normalizeStringValue']),
            new TwigFunction('formatter_simple_array_to_html', [$this, 'simpleArrayToHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Format a string.
     *
     * @param mixed $val
     */
    public function _($val, string $type = 'html', bool $textOnly = false, int $round = 1): string
    {
        return (string) $this->formatterHelper->_($val, $type, $textOnly, $round);
    }

    /**
     * @see FormatterHelper::normalizeStringValue
     */
    public function normalizeStringValue(string $string): string
    {
        return $this->formatterHelper->normalizeStringValue($string);
    }

    /**
     * @param array<mixed> $array
     */
    public function simpleArrayToHtml(array $array, string $delimeter = '<br />'): string
    {
        return $this->formatterHelper->simpleArrayToHtml($array, $delimeter);
    }
}
