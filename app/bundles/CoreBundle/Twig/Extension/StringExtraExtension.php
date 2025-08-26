<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Symfony\Component\String\UnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class StringExtraExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('u', [$this, 'createUnicodeString'], ['is_safe' => ['html']]),
        ];
    }

    public function createUnicodeString(string $string): UnicodeString
    {
        return new UnicodeString($string);
    }
}
