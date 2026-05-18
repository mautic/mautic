<?php

namespace Mautic\EmailBundle\Helper;

class UrlMatcher
{
    /**
     * @param string[] $urlsToCheckAgainst
     * @param string   $urlToFind
     */
    public static function hasMatch(array $urlsToCheckAgainst, $urlToFind): bool
    {
        $urlToFind = self::sanitizeUrl($urlToFind);

        foreach ($urlsToCheckAgainst as $url) {
            $url = self::sanitizeUrl($url);

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
            $url = str_replace('//', '', $url);
        }

        return $url;
    }
}
