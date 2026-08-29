<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Intl\Languages;
use Twig\Attribute\AsTwigFilter;

final readonly class LanguageExtension
{
    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * Returns the language name for the given language code.
     *
     * @param string      $code          The language code (e.g., 'en', 'fr', etc.)
     * @param string|null $displayLocale The locale used to display the language name (defaults to user's locale)
     *
     * @return string The language name
     */
    #[AsTwigFilter(name: 'language_name')]
    public function getLanguageName(string $code, ?string $displayLocale = null): string
    {
        $displayLocale ??= $this->getCurrentUserLocale();

        try {
            return Languages::getName($code, $displayLocale) ?: $code;
        } catch (\Exception) {
            return $code;
        }
    }

    /**
     * Get the current user's locale or fall back to 'en'.
     */
    private function getCurrentUserLocale(): string
    {
        $user = $this->security->getUser();
        if ($user instanceof User && $user->getLocale()) {
            return $user->getLocale();
        }

        return 'en';
    }
}
