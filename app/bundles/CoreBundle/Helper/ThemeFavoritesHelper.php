<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;

/**
 * Keeps the favorite themes of the logged-in user.
 *
 * The favorites are stored in the user preferences, separately for each theme type.
 * Thus, each user has their own favorites, and a favorite email theme is not automatically a favorite page theme.
 */
final readonly class ThemeFavoritesHelper
{
    public const PREFERENCE_KEY = 'theme_favorites';

    /**
     * Theme types that support favorites.
     */
    public const TYPES = ['email', 'page'];

    public function __construct(
        private UserHelper $userHelper,
        private UserRepository $userRepository,
        private ThemeHelperInterface $themeHelper,
    ) {
    }

    /**
     * @return string[] the keys (directory names) of the favorite themes, in the order of the installed themes
     */
    public function getFavorites(string $type): array
    {
        $user = $this->userHelper->getUser(true);

        if (null === $user || !$this->isSupportedType($type)) {
            return [];
        }

        return $this->readFavorites($user, $type);
    }

    public function isFavorite(string $theme, string $type): bool
    {
        return in_array($theme, $this->getFavorites($type), true);
    }

    /**
     * @throws \InvalidArgumentException if the type does not support favorites or the theme is not installed for the type
     * @throws \RuntimeException         if no user is logged in
     */
    public function setFavorite(string $theme, string $type, bool $favorite): void
    {
        if (!$this->isSupportedType($type)) {
            throw new \InvalidArgumentException(sprintf('The theme type "%s" does not support favorites.', $type));
        }

        $installedThemes = array_map(strval(...), array_keys($this->themeHelper->getInstalledThemes($type)));

        if (!in_array($theme, $installedThemes, true)) {
            throw new \InvalidArgumentException(sprintf('The theme "%s" is not installed for the type "%s".', $theme, $type));
        }

        $user = $this->userHelper->getUser(true);

        if (null === $user) {
            throw new \RuntimeException('Only a logged-in user can have favorite themes.');
        }

        $favorites = array_diff($this->readFavorites($user, $type), [$theme]);

        if ($favorite) {
            $favorites[] = $theme;
        }

        // Keep the order of the installed themes and remove the themes that are not installed anymore.
        $favorites = array_values(array_intersect($installedThemes, $favorites));

        $preferences = $user->getPreferences();

        if (!is_array($preferences[self::PREFERENCE_KEY] ?? null)) {
            $preferences[self::PREFERENCE_KEY] = [];
        }

        $preferences[self::PREFERENCE_KEY][$type] = $favorites;

        $user->setPreferences($preferences);
        $this->userRepository->saveEntity($user);
    }

    private function isSupportedType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * @return string[]
     */
    private function readFavorites(User $user, string $type): array
    {
        $favorites = $user->getPreferences()[self::PREFERENCE_KEY][$type] ?? [];

        if (!is_array($favorites)) {
            return [];
        }

        return array_values(array_filter($favorites, is_string(...)));
    }
}
