<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Intl\Languages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LanguageExtension extends AbstractExtension
{
    public function __construct(private Security $security)
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('language_name', [$this, 'getLanguageName']),
        ];
    }

    /**
     * Returns the language name for the given language code.
     *
     * @param string      $code          The language code (e.g., 'en', 'fr', etc.)
     * @param string|null $displayLocale The locale used to display the language name (defaults to user's locale)
     *
     * @return string The language name
     */
    public function getLanguageName(string $code, ?string $displayLocale = null): string
    {
        if (null === $displayLocale) {
            $displayLocale = $this->getCurrentUserLocale();
        }

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
