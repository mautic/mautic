<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class ObjectExtension extends AbstractExtension
{
    public function getTests()
    {
        return [
            new TwigTest('object', fn ($value) => is_object($value)),
        ];
    }
}
