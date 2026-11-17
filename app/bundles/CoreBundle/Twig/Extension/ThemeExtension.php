<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\ThemeFavoritesHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class ThemeExtension
{
    public function __construct(
        private ThemeHelper $themeHelper,
        private TranslatorInterface $translator,
        private ThemeFavoritesHelper $themeFavoritesHelper,
    ) {
    }

    /**
     * Get the theme display name for the specified theme.
     */
    #[AsTwigFunction(name: 'getThemeName')]
    public function getThemeName(string $theme = 'current'): string
    {
        // Special case for Code Mode
        if ('mautic_code_mode' === $theme) {
            return $this->translator->trans('mautic.core.code.mode');
        }

        $themeConfig = $this->themeHelper->getTheme($theme)->getConfig();

        return $themeConfig['name'] ?? $theme;
    }

    /**
     * Get the keys of the themes that the logged-in user marked as favorites for the theme type.
     *
     * @return string[]
     */
    #[AsTwigFunction(name: 'getFavoriteThemes')]
    public function getFavoriteThemes(string $type): array
    {
        return $this->themeFavoritesHelper->getFavorites($type);
    }
}
