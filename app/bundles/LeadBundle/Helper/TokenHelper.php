<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /**
     * @param string $content
     * @param array  $lead
     * @param bool   $replace If true, search/replace will be executed on $content and the modified $content returned
     *                        rather than an array of found matches
     *
     * @return array|string
     */
    public static function findLeadTokens($content, $lead, $replace = false)
    {
        // Search for bracket or bracket encoded
        // @deprecated BC support for leadfield
        $tokenRegex = [
            '/({|%7B)leadfield=(.*?)(}|%7D)/',
            '/({|%7B)contactfield=(.*?)(}|%7D)/',
        ];
        $tokenList = [];

        foreach ($tokenRegex as $regex) {
            $foundMatches = preg_match_all($regex, $content, $matches);
            if ($foundMatches) {
                foreach ($matches[2] as $key => $match) {
                    $token = $matches[0][$key];

                    if (isset($tokenList[$token])) {
                        continue;
                    }

                    $fallbackCheck = explode('|', $match);
                    $urlencode     = false;
                    $fallback      = '';

                    if (isset($fallbackCheck[1])) {
                        // There is a fallback or to be urlencoded
                        $alias = $fallbackCheck[0];

                        if ($fallbackCheck[1] === 'true') {
                            $urlencode = true;
                            $fallback  = '';
                        } else {
                            $fallback = $fallbackCheck[1];
                        }
                    } else {
                        $alias = $match;
                    }

                    $value             = (!empty($lead[$alias])) ? $lead[$alias] : $fallback;
                    $tokenList[$token] = ($urlencode) ? urlencode($value) : $value;
                }

                if ($replace) {
                    $content = str_replace(array_keys($tokenList), $tokenList, $content);
                }
            }
        }

        return $replace ? $content : $tokenList;
    }
}
