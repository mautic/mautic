<?php

namespace Mautic\EmailBundle\Helper;

class UrlMatcher
{
    public static function hasMatch(array $urlsToCheckAgainst, $urlToFind): bool
    {
        $urlToFind = self::sanitizeUrl((string) $urlToFind);

        foreach ($urlsToCheckAgainst as $url) {
            $url = (string) $url;

            if ('' === $url) {
                continue;
            }

            $url = self::sanitizeUrl($url);

            if (self::isLegacyWildcardPattern($url) && fnmatch($url, $urlToFind, FNM_CASEFOLD)) {
                return true;
            }

            if (preg_match('/'.preg_quote($url, '/').'/i', $urlToFind)) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeUrl(string $url): string
    {
        return self::sanitizeUrl($url);
    }

    /**
     * @return mixed|string
     */
    private static function sanitizeUrl($url)
    {
        // Handle escaped forward slashes as BC
        $url = str_replace('\\/', '/', $url);

        // Ignore ending slash
        $url = rtrim($url, '/');

        // Ignore http/https
        $url = str_replace(['http://', 'https://'], '', $url);

        // Remove preceding //
        if (str_starts_with($url, '//')) {
            $url = str_replace('//', '', $url);
        }

        return $url;
    }

    private static function isLegacyWildcardPattern(string $url): bool
    {
        return false !== strpbrk($url, '*?[');
    }
}
