<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\FormBundle\Enum\ConditionalFieldEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EnumExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('enumConditionalFieldTypes', ConditionalFieldEnum::getConditionalFieldTypes(...)),
        ];
    }
}
