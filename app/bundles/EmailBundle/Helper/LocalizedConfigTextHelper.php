<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class LocalizedConfigTextHelper
{
    public const string SUFFIX = '_translations';

    public const array KEYS = [
        'unsubscribe_text',
        'webview_text',
        'unsubscribe_message',
        'resubscribe_message',
        'default_signature_text',
    ];

    public static function get(CoreParametersHelper $coreParametersHelper, string $key, ?string $locale): string
    {
        $translations = $coreParametersHelper->get($key.self::SUFFIX);

        if (is_array($translations) && [] !== $translations) {
            $systemLocale = (string) $coreParametersHelper->get('locale');
            $candidates   = array_filter([$locale, self::language($locale), $systemLocale, self::language($systemLocale)]);

            foreach (array_unique($candidates) as $candidate) {
                if (isset($translations[$candidate]) && is_string($translations[$candidate]) && '' !== trim($translations[$candidate])) {
                    return $translations[$candidate];
                }
            }
        }

        return (string) $coreParametersHelper->get($key);
    }

    private static function language(?string $locale): ?string
    {
        return $locale ? explode('_', $locale)[0] : null;
    }
}
