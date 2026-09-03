<?php

namespace Mautic\EmailBundle\Helper;

final class UrlMatcher
{
    /**
     * @param array<array-key, string> $urlsToCheckAgainst
     */
    public static function hasMatch(array $urlsToCheckAgainst, string $urlToFind): bool
    {
        $urlToFind = self::sanitizeUrl($urlToFind);

        foreach ($urlsToCheckAgainst as $url) {
            $url = (string) $url;

            if ('' === $url) {
                continue;
            }

            $url = self::sanitizeUrl($url);

            if (self::isLegacyWildcardPattern($url) && \fnmatch($url, $urlToFind, FNM_CASEFOLD)) {
                return true;
            }

            if (preg_match('/'.preg_quote($url, '/').'/i', $urlToFind)) {
                return true;
            }
        }

        return false;
    }

    private static function sanitizeUrl(string $url): string
    {
        // Handle escaped forward slashes as BC
        $url = str_replace('\\/', '/', $url);

        // Only decode square brackets for array notation normalization
        // %5B = [ and %5D = ]
        $url = str_replace(['%5B', '%5b', '%5D', '%5d'], ['[', '[', ']', ']'], $url);

        // Normalize array parameter notation: convert [0], [1], etc. to []
        $url = preg_replace('/\[\d+\]/', '[]', $url);

        // Ignore ending slash
        $url = rtrim($url, '/');

        // Ignore http/https
        $url = str_replace(['http://', 'https://'], '', $url);

        // Remove preceding //
        if (str_starts_with($url, '//')) {
            return str_replace('//', '', $url);
        }

        return $url;
    }

    private static function isLegacyWildcardPattern(string $url): bool
    {
        return false !== \strpbrk($url, '*?[');
    }
}
