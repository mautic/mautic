<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\Serializer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SerializerExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('serializerDecode', Serializer::decode(...)),
        ];
    }
}
