<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\MautibotHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MautibotExtension extends AbstractExtension
{
    public function __construct(
        protected MautibotHelper $mautibotHelper
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('mautibotGetImage', [$this, 'getImage'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @param string $image One of openMouth | smile | wave
     */
    public function getImage(string $image): string
    {
        return $this->mautibotHelper->getImage($image);
    }
}
