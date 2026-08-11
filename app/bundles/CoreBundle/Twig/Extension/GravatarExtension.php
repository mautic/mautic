<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class GravatarExtension
{
    public function __construct(
        private GravatarHelper $gravatarHelper,
    ) {
    }

    #[AsTwigFunction(name: 'gravatarGetImage', isSafe: ['all'])]
    public function getImage(string $email, string $size = '250', ?string $default = null): string
    {
        return $this->gravatarHelper->getImage($email, $size, $default);
    }
}
