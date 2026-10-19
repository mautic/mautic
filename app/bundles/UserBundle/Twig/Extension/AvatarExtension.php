<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Twig\Extension;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarExtension extends AbstractExtension
{
    private const FALLBACK_IMAGE = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2NCA2NCI+PGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMzIiIGZpbGw9IiNlMGUwZTAiLz48L3N2Zz4=';

    private ?Style $style = null;

    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
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
            return self::FALLBACK_IMAGE;
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
