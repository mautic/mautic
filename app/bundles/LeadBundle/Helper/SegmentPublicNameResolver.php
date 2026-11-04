<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

final class SegmentPublicNameResolver
{
    public function __construct(private readonly SegmentPublicNameLocaleNormalizer $localeNormalizer)
    {
    }

    /**
     * @param array<string, string> $translationsByLocale
     */
    public function resolve(?string $preferredLocale, ?string $defaultPublicName, string $internalName, array $translationsByLocale): string
    {
        if (null !== $preferredLocale && '' !== trim($preferredLocale)) {
            try {
                $locale = $this->localeNormalizer->normalize($preferredLocale);
                if (isset($translationsByLocale[$locale]) && '' !== trim($translationsByLocale[$locale])) {
                    return $translationsByLocale[$locale];
                }

                $language = explode('_', $locale, 2)[0];
                if ($language !== $locale && isset($translationsByLocale[$language]) && '' !== trim($translationsByLocale[$language])) {
                    return $translationsByLocale[$language];
                }
            } catch (\InvalidArgumentException) {
                // An invalid contact locale must not hide a public segment.
            }
        }

        return $defaultPublicName ?: $internalName;
    }
}
