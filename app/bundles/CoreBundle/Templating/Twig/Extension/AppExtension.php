<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_class', fn (string $value) => class_exists($value)),
            new TwigFunction('is_file', fn (string $value) => file_exists($value)),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('string', fn ($value) => is_string($value)),
            new TwigTest('class', fn (string $value) => class_exists($value)),
            new TwigTest('file', fn (string $value) => file_exists($value)),
        ];
    }
}
