<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

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

    /**
     * @param 
     */
    public function getRoot()
    {
        $root = realpath(__DIR__.'/../../../../../../');

        return $root;
    }
    
}
