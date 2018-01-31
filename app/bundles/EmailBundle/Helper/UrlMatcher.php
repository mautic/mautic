<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

class UrlMatcher
{
    /**
     * @param array $urlsToCheckAgainst
     * @param       $urlToFind
     *
     * @return bool
     */
    public static function hasMatch(array $urlsToCheckAgainst, $urlToFind)
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
     * @param $url
     *
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
        if (strpos($url, '//') === 0) {
            $url = str_replace('//', '', $url);
        }

        return $url;
    }
}
