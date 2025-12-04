<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\GravatarHelper;

class GravatarExtension
{
    public function __construct(
        protected GravatarHelper $gravatarHelper,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction('gravatarGetImage', isSafe: ['all'])]
    public function getImage(string $email, string $size = '250', ?string $default = null): string
    {
        return $this->gravatarHelper->getImage($email, $size, $default);
    }
}
