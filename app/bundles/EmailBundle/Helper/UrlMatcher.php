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
        foreach ($urlsToCheckAgainst as $url) {
            $url = str_replace('\\/', '/', $url);
            if (preg_match('/'.preg_quote($url, '/').'/i', $urlToFind)) {
                return true;
            }
        }

        return false;
    }
}
