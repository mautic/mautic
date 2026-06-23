<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('ini_get', fn ($value): string|false => ini_get($value)),
            new TwigFunction('is_class', fn (string $value): bool => class_exists($value)),
            new TwigFunction('is_file', fn (string $value): bool => file_exists($value)),
            new TwigFunction('is_function', fn (string $value): bool => function_exists($value)),
            new TwigFunction('is_extension_loaded', fn (string $value): bool => extension_loaded($value)),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('string', fn ($value): bool => is_string($value)),
            new TwigTest('class', fn (string $value): bool => class_exists($value)),
            new TwigTest('file', fn (string $value): bool => file_exists($value)),
            new TwigTest('function', fn (string $value): bool => function_exists($value)),
        ];
    }
}
