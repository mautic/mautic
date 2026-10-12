<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Twig\Extension;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarExtension extends AbstractExtension
{
    private ?Style $style = null;

    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('userAvatar', $this->getImage(...))];
    }

    public function getImage(int $userId): string
    {
        if (null === $this->style) {
            $definition = file_get_contents(InstalledVersions::getInstallPath('dicebear/styles').'/src/line-face.json');

            if (false === $definition) {
                throw new \RuntimeException('Unable to load the Line Face avatar style.');
            }

            $this->style = Style::fromJson($definition);
        }

        $siteUrl = rtrim((string) $this->coreParametersHelper->get('site_url'), '/');

        return (new Avatar($this->style, ['seed' => $siteUrl.'/user-'.$userId]))->toDataUri();
    }
}
