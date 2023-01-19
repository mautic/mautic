<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\InputHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InputExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('inputUrl', [InputHelper::class, 'url']),
        ];
    }
}
