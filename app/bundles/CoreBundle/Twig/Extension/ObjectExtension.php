<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class ObjectExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('method_exists', fn ($obj, $method) => method_exists($obj, $method)),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('object', fn ($value) => is_object($value)),
        ];
    }
}
