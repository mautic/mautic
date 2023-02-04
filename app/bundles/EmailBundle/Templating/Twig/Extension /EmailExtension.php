<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EmailExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('instanceof', [$this, 'isInstanceof']),
        ];
    }

    public function isInstanceOf(object $object, object $class): bool
    {
        return $object instanceof $class;
    }
}
