<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatterExtension extends AbstractExtension
{
    protected FormatterHelper $formatterHelper;

    public function __construct(FormatterHelper $formatterHelper)
    {
        $this->formatterHelper = $formatterHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatter_simple_array_to_html', [$this, 'simpleArrayToHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
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
        return $this->formatterHelper->_($val, $type, $textOnly, $round);
    }

    /**
     * @see FormatterHelper::normalizeStringValue
     */
    public function normalizeStringValue(string $string): string
    {
        return $this->formatterHelper->normalizeStringValue($string);
    }

    public function simpleArrayToHtml(array $array, string $delimeter = '<br />'): string
    {
        $pairs = [];
        foreach ($array as $key => $value) {
            $pairs[] = "$key: $value";
        }

        return implode($delimeter, $pairs);
    }
}
