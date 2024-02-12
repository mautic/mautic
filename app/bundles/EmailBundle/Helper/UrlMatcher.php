<?php

namespace Mautic\EmailBundle\Helper;

class UrlMatcher
{
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
}
