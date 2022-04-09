<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Helper for getting a class reference from a Twig template.
 */
class ClassExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('get_class', fn ($value) => (new \ReflectionClass($value))->getShortName()),
        ];
    }
}
