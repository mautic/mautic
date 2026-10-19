<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Twig\Extension;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarExtension extends AbstractExtension
{
    private ?Style $style = null;

    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly DefaultAvatarHelper $defaultAvatarHelper,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('userAvatar', $this->getImage(...))];
    }

    public function getImage(int $userId): string
    {
        $style = $this->loadStyle();

        if (null === $style) {
            return $this->defaultAvatarHelper->getDefaultAvatar(true);
        }

        $siteUrl = rtrim((string) $this->coreParametersHelper->get('site_url'), '/');

        return (new Avatar($style, ['seed' => $siteUrl.'/user-'.$userId]))->toDataUri();
    }

    private function loadStyle(): ?Style
    {
        if (null !== $this->style) {
            return $this->style;
        }

        try {
            $installPath = InstalledVersions::getInstallPath('dicebear/styles');
            $file        = null === $installPath ? null : $installPath.'/src/line-face.json';
            $definition  = null !== $file && is_readable($file) ? file_get_contents($file) : false;

            if (false === $definition) {
                $this->logger?->error('Line Face avatar style file not found.', ['path' => $file]);

                return null;
            }

            return $this->style = Style::fromJson($definition);
        } catch (\Throwable $exception) {
            $this->logger?->error('Unable to load the Line Face avatar style.', ['exception' => $exception]);

            return null;
        }
    }
}
