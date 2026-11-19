<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\ThemeFavoritesHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeHelper $themeHelper,
        private readonly TranslatorInterface $translator,
        private readonly ThemeFavoritesHelper $themeFavoritesHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getThemeName', $this->getThemeName(...)),
            new TwigFunction('getFavoriteThemes', $this->getFavoriteThemes(...)),
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

    /**
     * Get the keys of the themes that the logged-in user marked as favorites for the theme type.
     *
     * @return string[]
     */
    public function getFavoriteThemes(string $type): array
    {
        return $this->themeFavoritesHelper->getFavorites($type);
    }
}
