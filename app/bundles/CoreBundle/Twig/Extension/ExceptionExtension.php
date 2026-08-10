<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Attribute\AsTwigFunction;

final class ExceptionExtension
{
    #[AsTwigFunction(name: 'getRootPath', isSafe: ['all'])]
    public function getRoot(): string
    {
        return realpath(__DIR__.'/../../../../../../');
    }
}
