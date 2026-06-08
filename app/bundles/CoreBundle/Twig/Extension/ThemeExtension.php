<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private ThemeHelper $themeHelper,
        private TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getThemeName', [$this, 'getThemeName']),
        ];
    }

    /**
     * Get the theme display name for the specified theme.
     */
    public function getThemeName(string $theme = 'current'): string
    {
        // Special case for Code Mode
        if ('mautic_code_mode' === $theme) {
            return $this->translator->trans('mautic.core.code.mode');
        }

        $themeConfig = $this->themeHelper->getTheme($theme)->getConfig();

        return $themeConfig['name'] ?? $theme;
    }
}
