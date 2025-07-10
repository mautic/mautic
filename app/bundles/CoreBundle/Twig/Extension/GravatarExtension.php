<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GravatarExtension extends AbstractExtension
{
    public function __construct(
        protected GravatarHelper $gravatarHelper,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('gravatarGetImage', [$this, 'getImage'], ['is_safe' => ['all']]),
        ];
    }

    public function getImage(string $email, string $size = '250', ?string $default = null): string
    {
        return $this->gravatarHelper->getImage($email, $size, $default);
    }
}
