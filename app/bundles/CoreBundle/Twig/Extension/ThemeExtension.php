<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeExtension
{
    public function __construct(
        private ThemeHelper $themeHelper,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Get the theme display name for the specified theme.
     */
    #[\Twig\Attribute\AsTwigFunction('getThemeName')]
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
