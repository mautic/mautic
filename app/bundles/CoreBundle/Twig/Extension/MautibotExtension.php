<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\MautibotHelper;

class MautibotExtension
{
    public function __construct(
        protected MautibotHelper $mautibotHelper,
    ) {
    }

    /**
     * @param string $image One of openMouth | smile | wave
     */
    #[\Twig\Attribute\AsTwigFunction('mautibotGetImage', isSafe: ['all'])]
    public function getImage(string $image): string
    {
        return $this->mautibotHelper->getImage($image);
    }
}
