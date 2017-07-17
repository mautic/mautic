<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Joomla\Filter\InputFilter;

/**
 * Class InputHelper.
 */
class InputHelper
{
    /**
     * String filter.
     *
     * @var InputFilter
     */
    private static $stringFilter;

    /**
     * HTML filter.
     *
     * @var InputFilter
     */
    private static $htmlFilter;

    /**
     * @var
     */
    private static $strictHtmlFilter;

    /**
     * @param bool $html
     * @param bool $strict
     *
     * @return InputFilter
     */
    private static function getFilter($html = false, $strict = false)
    {
        if (empty(self::$htmlFilter)) {
            // Most of Mautic's HTML uses include full HTML documents so use blacklist method
            self::$htmlFilter               = new InputFilter([], [], 1, 1);
            self::$htmlFilter->tagBlacklist = [
                'applet',
                'bgsound',
                'base',
                'basefont',
                'embed',
                'frame',
                'frameset',
                'ilayer',
                'layer',
                'object',
            ];

            self::$htmlFilter->attrBlacklist = [
                'codebase',
                'dynsrc',
                'lowsrc',
            ];

            // Strict HTML - basic one liner formating really
            self::$strictHtmlFilter = new InputFilter(
                [
                    'b',
                    'i',
                    'u',
                    'em',
                    'strong',
                    'a',
                    'span',
                ], [], 0, 1);

            self::$strictHtmlFilter->attrBlacklist = [
                'codebase',
                'dynsrc',
                'lowsrc',
            ];

            // Standard behavior if HTML is not specifically used
            self::$stringFilter = new InputFilter();
        }

        switch (true) {
            case $html:
                return ($strict) ? self::$strictHtmlFilter : self::$htmlFilter;
            default:
                return self::$stringFilter;
        }
    }

    /**
     * Wrapper to InputHelper.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getFilter()->clean($arguments[0], $name);
    }

    /**
     * Wrapper function to clean inputs.  $mask can be an array of keys as the field names and values as the cleaning
     * function to be used for the specific field.
     *
     * @param mixed $value
     * @param mixed $mask
     * @param bool  $urldecode
     *
     * @return mixed
     */
    public static function _($value, $mask = 'clean', $urldecode = false)
    {
        if (is_array($value)) {
            foreach ($value as $k => &$v) {
                $useMask = 'filter';
                if (is_array($mask)) {
                    if (array_key_exists($k, $mask)) {
                        if (is_array($mask[$k])) {
                            $useMask = $mask[$k];
                        } elseif (method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask[$k])) {
                            $useMask = $mask[$k];
                        }
                    } elseif (is_array($v)) {
                        // Likely a collection so use the same mask
                        $useMask = $mask;
                    }
                } elseif (method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
                    $useMask = $mask;
                }

                if (is_array($v)) {
                    $v = self::_($v, $useMask, $urldecode);
                } elseif ($useMask == 'filter') {
                    $v = self::getFilter()->clean($v, $useMask);
                } else {
                    $v = self::$useMask($v, $urldecode);
                }
            }

            return $value;
        } elseif (is_string($mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
            return self::$mask($value, $urldecode);
        } else {
            return self::getFilter()->clean($value, $mask);
        }
    }

    /**
     * Cleans value by HTML-escaping '"<>& and characters with ASCII value less than 32.
     *
     * @param            $value
     * @param bool|false $urldecode
     *
     * @return mixed|string
     */
    public static function clean($value, $urldecode = false)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::clean($v, $urldecode);
            }

            return $value;
        } elseif ($urldecode) {
            $value = urldecode($value);
        }

        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Strips tags.
     *
     * @param            $value
     * @param bool|false $urldecode
     *
     * @return mixed
     */
    public static function string($value, $urldecode = false)
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    /**
     * Strips non-alphanumeric characters.
     *
     * @param            $value
     * @param bool|false $urldecode
     * @param bool|false $convertSpacesTo
     * @param array      $allowedCharacters
     *
     * @return string
     */
    public static function alphanum($value, $urldecode = false, $convertSpacesTo = false, $allowedCharacters = [])
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        if ($convertSpacesTo) {
            $value               = str_replace(' ', $convertSpacesTo, $value);
            $allowedCharacters[] = $convertSpacesTo;
        }

        $delimiter = '~';
        if (false && in_array($delimiter, $allowedCharacters)) {
            $delimiter = '#';
        }

        if (!empty($allowedCharacters)) {
            $regex = $delimiter.'[^0-9a-z'.preg_quote(implode('', $allowedCharacters)).']+'.$delimiter.'i';
        } else {
            $regex = $delimiter.'[^0-9a-z]+'.$delimiter.'i';
        }

        return trim(preg_replace($regex, '', $value));
    }

    /**
     * Returns a satnitized string which can be used in a file system.
     *
     * @param  $value
     *
     * @return string
     */
    public static function filename($value)
    {
        $value = str_replace(' ', '_', $value);

        return preg_replace("/[^a-z0-9\.\_]/", '', strtolower($value));
    }

    /**
     * Returns raw value.
     *
     * @param            $value
     * @param bool|false $urldecode
     *
     * @return string
     */
    public static function raw($value, $urldecode = false)
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        return $value;
    }

    /**
     * Removes all characters except those allowed in URLs.
     *
     * @param            $value
     * @param bool|false $urldecode
     * @param null       $allowedProtocols
     * @param null       $defaultProtocol
     * @param array      $removeQuery
     * @param bool|false $ignoreFragment
     *
     * @return mixed|string
     */
    public static function url($value, $urldecode = false, $allowedProtocols = null, $defaultProtocol = null, $removeQuery = [], $ignoreFragment = false)
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        if (empty($allowedProtocols)) {
            $allowedProtocols = ['https', 'http', 'ftp'];
        }
        if (empty($defaultProtocol)) {
            $defaultProtocol = 'http';
        }

        $value = filter_var($value, FILTER_SANITIZE_URL);
        $parts = parse_url($value);

        if ($parts && !empty($parts['path'])) {
            if (isset($parts['scheme'])) {
                if (!in_array($parts['scheme'], $allowedProtocols)) {
                    $parts['scheme'] = $defaultProtocol;
                }
            } else {
                $parts['scheme'] = $defaultProtocol;
            }

            if (!empty($removeQuery) && !empty($parts['query'])) {
                parse_str($parts['query'], $query);
                foreach ($removeQuery as $q) {
                    if (isset($query[$q])) {
                        unset($query[$q]);
                    }
                }
                $parts['query'] = http_build_query($query);
            }

            $value =
                (!empty($parts['scheme']) ? $parts['scheme'].'://' : '').
                (!empty($parts['user']) ? $parts['user'].':' : '').
                (!empty($parts['pass']) ? $parts['pass'].'@' : '').
                (!empty($parts['host']) ? $parts['host'] : '').
                (!empty($parts['port']) ? ':'.$parts['port'] : '').
                (!empty($parts['path']) ? $parts['path'] : '').
                (!empty($parts['query']) ? '?'.$parts['query'] : '').
                (!$ignoreFragment && !empty($parts['fragment']) ? '#'.$parts['fragment'] : '');
        } else {
            //must have a really bad URL since parse_url returned false so let's just clean it
            $value = self::clean($value);
        }

        //since a URL allows <>, let's add a safety step to remove <script> tags
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);

        return $value;
    }

    /**
     * Removes all characters except those allowed in emails.
     *
     * @param            $value
     * @param bool|false $urldecode
     *
     * @return mixed
     */
    public static function email($value, $urldecode = false)
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        $value = substr($value, 0, 254);

        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Returns a clean array.
     *
     * @param            $value
     * @param bool|false $urldecode
     *
     * @return array|mixed|string
     */
    public static function cleanArray($value, $urldecode = false)
    {
        $value = self::clean($value, $urldecode);

        if (!is_array($value)) {
            $value = [$value];
        }

        return $value;
    }

    /**
     * Returns clean HTML.
     *
     * @param $value
     *
     * @return mixed|string
     */
    public static function html($value)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = self::html($val);
            }
        } else {
            // Special handling for doctype
            $doctypeFound = preg_match('/(<!DOCTYPE(.*?)>)/is', $value, $doctype);

            // Special handling for CDATA tags
            $value = str_replace(['<![CDATA[', ']]>'], ['<mcdata>', '</mcdata>'], $value, $cdataCount);

            // Special handling for conditional blocks
            $value = preg_replace("/<!--\[if(.*?)\]>(.*?)<!\[endif\]-->/is", '<mcondition><mif>$1</mif>$2</mcondition>', $value, -1, $conditionsFound);

            // Slecial handling for XML tags used in Outlook optimized emails <o:*/> and <w:/>
            $value = preg_replace_callback(
                "/<\/*[o|w|v]:[^>]*>/is",
                function ($matches) {
                    return '<mencoded>'.htmlspecialchars($matches[0]).'</mencoded>';
                },
                $value, -1, $needsDecoding);

            // Special handling for HTML comments
            $value = str_replace(['<!--', '-->'], ['<mcomment>', '</mcomment>'], $value, $commentCount);

            $value = self::getFilter(true)->clean($value, 'html');

            // Was a doctype found?
            if ($doctypeFound) {
                $value = "$doctype[0]$value";
            }

            if ($cdataCount) {
                $value = str_replace(['<mcdata>', '</mcdata>'], ['<![CDATA[', ']]>'], $value);
            }

            if ($conditionsFound) {
                // Special handling for conditional blocks
                $value = preg_replace("/<mcondition><mif>(.*?)<\/mif>(.*?)<\/mcondition>/is", '<!--[if$1]>$2<![endif]-->', $value);
            }

            if ($commentCount) {
                $value = str_replace(['<mcomment>', '</mcomment>'], ['<!--', '-->'], $value);
            }

            $value = preg_replace_callback(
                "/<mencoded>(.*?)<\/mencoded>/is",
                function ($matches) {
                    return htmlspecialchars_decode($matches[1]);
                },
                $value);
        }

        return $value;
    }

    /**
     * Allows tags 'b', 'i', 'u', 'em', 'strong', 'a', 'span'.
     *
     * @param $data
     *
     * @return mixed|string
     */
    public static function strict_html($value)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = self::strict_html($val);
            }
        }

        return self::getFilter(true, true)->clean($value, 'html');
    }

    /**
     * Converts UTF8 into Latin.
     *
     * @param $value
     *
     * @return mixed
     */
    public static function transliterate($value)
    {
        $transId = 'Any-Latin; Latin-ASCII';
        if (function_exists('transliterator_transliterate') && $trans = \Transliterator::create($transId)) {
            // Use intl by default
            return $trans->transliterate($value);
        }

        return \URLify::transliterate($value);
    }
}
