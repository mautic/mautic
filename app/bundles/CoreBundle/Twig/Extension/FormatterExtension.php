<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final readonly class FormatterExtension
{
    public function __construct(
        private FormatterHelper $formatterHelper,
    ) {
    }

    /**
     * Format a string.
     *
     * @param mixed $val
     */
    #[AsTwigFunction(name: 'format', isSafe: ['all'])]
    public function _($val, string $type = 'html', bool $textOnly = false, int $round = 1): string
    {
        return (string) $this->formatterHelper->_($val, $type, $textOnly, $round);
    }

    /**
     * @see FormatterHelper::normalizeStringValue
     */
    #[AsTwigFunction(name: 'normalizeStringValue')]
    public function normalizeStringValue(string $string): string
    {
        return $this->formatterHelper->normalizeStringValue($string);
    }

    /**
     * @param array<mixed> $array
     */
    #[AsTwigFilter(name: 'formatter_simple_array_to_html', isSafe: ['html'])]
    #[AsTwigFunction(name: 'formatter_simple_array_to_html', isSafe: ['html'])]
    public function simpleArrayToHtml(array $array, string $delimeter = '<br />'): string
    {
        return $this->formatterHelper->simpleArrayToHtml($array, $delimeter);
    }
}
