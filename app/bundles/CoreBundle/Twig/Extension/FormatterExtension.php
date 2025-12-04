<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\FormatterHelper;

class FormatterExtension
{
    public function __construct(
        protected FormatterHelper $formatterHelper,
    ) {
    }

    /**
     * Format a string.
     *
     * @param mixed $val
     */
    #[\Twig\Attribute\AsTwigFunction('format', isSafe: ['all'])]
    public function _($val, string $type = 'html', bool $textOnly = false, int $round = 1): string
    {
        return (string) $this->formatterHelper->_($val, $type, $textOnly, $round);
    }

    /**
     * @see FormatterHelper::normalizeStringValue
     */
    #[\Twig\Attribute\AsTwigFunction('normalizeStringValue')]
    public function normalizeStringValue(string $string): string
    {
        return $this->formatterHelper->normalizeStringValue($string);
    }

    /**
     * @param array<mixed> $array
     */
    #[\Twig\Attribute\AsTwigFilter('formatter_simple_array_to_html', isSafe: ['html'])]
    #[\Twig\Attribute\AsTwigFunction('formatter_simple_array_to_html', isSafe: ['html'])]
    public function simpleArrayToHtml(array $array, string $delimeter = '<br />'): string
    {
        return $this->formatterHelper->simpleArrayToHtml($array, $delimeter);
    }
}
