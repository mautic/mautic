<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ExceptionExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getRootPath', [$this, 'getRoot'], ['is_safe' => ['all']]),
        ];
    }

    public function getRoot(): string
    {
        return realpath(__DIR__.'/../../../../../../');
    }
}
