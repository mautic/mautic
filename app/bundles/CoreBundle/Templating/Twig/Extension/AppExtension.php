<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('string', fn ($value) => is_string($value)),
            new TwigTest('instanceof', [$this, 'isinstanceof']),
        ];
    }

    /**
     * @param object $object
     * @param object $class
     * @return bool
     */
    public function isInstanceOf(object $object, object $class): bool
    {
        return $object instanceof $class;
    }
}
