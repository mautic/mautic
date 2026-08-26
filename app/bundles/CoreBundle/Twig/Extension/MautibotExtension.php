<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\MautibotHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class MautibotExtension
{
    public function __construct(
        private MautibotHelper $mautibotHelper,
    ) {
    }

    /**
     * @param string $image One of openMouth | smile | wave
     */
    #[AsTwigFunction(name: 'mautibotGetImage', isSafe: ['all'])]
    public function getImage(string $image): string
    {
        return $this->mautibotHelper->getImage($image);
    }
}
