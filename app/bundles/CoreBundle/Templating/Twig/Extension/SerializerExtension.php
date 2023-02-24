<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\Serializer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SerializerExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('serializerDecode', [Serializer::class, 'decode']),
        ];
    }
}
