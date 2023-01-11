<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    public function getTests()
    {
        return [
            new TwigTest('instanceof', [$this, 'isinstanceof']),
            new TwigTest('string', [$this, 'is_string']),
        ];
    }

    public function isInstanceof(mixed $var, string $instance): bool
    {
        return $var instanceof $instance;
    }

    public function is_string(mixed $value): bool
    {
        return is_string($value);
    }
}
