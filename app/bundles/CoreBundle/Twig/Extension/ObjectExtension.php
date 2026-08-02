<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

final class ObjectExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('method_exists', fn ($obj, $method): bool => method_exists($obj, $method)),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('object', fn ($value): bool => is_object($value)),
        ];
    }
}
