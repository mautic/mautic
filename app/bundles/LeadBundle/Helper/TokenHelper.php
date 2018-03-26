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
        if (!$lead) {
            return $replace ? $content : [];
        }

        // Search for bracket or bracket encoded
        // @deprecated BC support for leadfield
        $tokenRegex = [
            '/({|%7B)leadfield=(.*?)(}|%7D)/',
            '/({|%7B)contactfield=(.*?)(}|%7D)/',
        ];
        $tokenList  = [];

        foreach ($tokenRegex as $regex) {
            $foundMatches = preg_match_all($regex, $content, $matches);
            if ($foundMatches) {
                foreach ($matches[2] as $key => $match) {
                    $token = $matches[0][$key];

                    if (isset($tokenList[$token])) {
                        continue;
                    }

                    $alias             = self::getFieldAlias($match);
                    $defaultValue      = self::getTokenDefaultValue($match);
                    $tokenList[$token] = self::getTokenValue($lead, $alias, $defaultValue);
                }

                if ($replace) {
                    $content = str_replace(array_keys($tokenList), $tokenList, $content);
                }
            }
        }

        return $replace ? $content : $tokenList;
    }

    /**
     * @param array $lead
     * @param       $alias
     * @param       $defaultValue
     *
     * @return mixed
     */
    private static function getTokenValue(array $lead, $alias, $defaultValue)
    {
        $value = '';
        if (isset($lead[$alias])) {
            $value = $lead[$alias];
        } elseif (isset($lead['companies'][0][$alias])) {
            $value = $lead['companies'][0][$alias];
        }

        if ('true' === $defaultValue) {
            $value = urlencode($value);
        }

        return $value ?: $defaultValue;
    }

    /**
     * @param $match
     * @param $urlencode
     *
     * @return string
     */
    private static function getTokenDefaultValue($match)
    {
        $fallbackCheck = explode('|', $match);
        if (!isset($fallbackCheck[1])) {
            return '';
        }

        return $fallbackCheck[1];
    }

    /**
     * @param $match
     *
     * @return mixed
     */
    private static function getFieldAlias($match)
    {
        $fallbackCheck = explode('|', $match);

        return $fallbackCheck[0];
    }
}
