<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\FormBundle\Enum\ConditionalFieldEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EnumExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('enumConditionalFieldTypes', [ConditionalFieldEnum::class, 'getConditionalFieldTypes']),
        ];
    }
}
