<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\GravatarHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GravatarExtension extends AbstractExtension
{
    protected GravatarHelper $gravatarHelper;

    public function __construct(GravatarHelper $gravatarHelper)
    {
        $this->gravatarHelper = $gravatarHelper;
    }

    /**
     * {@inheritdoc}
     */
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
