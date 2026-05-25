<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

final class BlockedFreeEmailProvidersHelper
{
    private const JSON_FILE_PATH = __DIR__.'/../Assets/json/blocked_free_email_providers.json';

    /**
     * Load blocked free email providers from JSON file.
     *
     * @return array<string>
     */
    public static function load(): array
    {
        $providers = self::loadRaw();

        return is_array($providers) ? array_map('strtolower', $providers) : [];
    }

    /**
     * @return array<string>|null
     */
    private static function loadRaw(): ?array
    {
        $decoded = null;
        if (file_exists(self::JSON_FILE_PATH) && is_readable(self::JSON_FILE_PATH)) {
            $content = file_get_contents(self::JSON_FILE_PATH);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
                    $decoded = null;
                }
            }
        }

        return $decoded;
    }
}
