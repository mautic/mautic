<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Joomla\Filter\InputFilter;


/**
 * Class InputHelper
 */
class InputHelper
{
    /**
     * String filter
     *
     * @var InputFilter
     */
    private static $stringFilter;

    /**
     * HTML filter
     *
     * @var InputFilter
     */
    private static $htmlFilter;

    private static function getFilter($html = false)
    {
        if (empty(self::$htmlFilter)) {
            // Most of Mautic's HTML uses include full HTML documents so use blacklist method
            self::$htmlFilter = new InputFilter(array(), array(), 1, 1);
            self::$htmlFilter->tagBlacklist = array(
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
                'xml'
            );

            self::$htmlFilter->attrBlacklist = array(
                'codebase',
                'dynsrc',
                'lowsrc'
            );

            // Standard behavior if HTML is not specifically used
            self::$stringFilter = new InputFilter();
        }

        return ($html) ? self::$htmlFilter : self::$stringFilter;
    }

    /**
     * Wrapper to InputHelper
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
                    }
                } elseif (method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
                    $useMask = $mask;
                }

                if (is_array($v) && is_array($useMask)) {
                    $v = self::_($v, $useMask, $urldecode);
                } elseif ($useMask == 'filter') {
                    $v = self::getFilter()->clean($v, $useMask);
                } else {
                    $v = self::$useMask($v, $urldecode);
                }
            }

            return $value;
        } elseif (is_string($mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
            if (is_array($value)) {
                foreach ($value as $k => &$v) {
                    $v = self::$mask($v, $urldecode);
                }
                return $value;
            } else {
                return self::$mask($value, $urldecode);
            }
        } else {
            return self::getFilter()->clean($value, $mask);
        }
    }

    /**
     * Cleans value by HTML-escaping '"<>& and characters with ASCII value less than 32
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
     * Strips tags
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
     * Strips non-alphanumeric characters
     *
     * @param            $value
     * @param bool|false $urldecode
     * @param bool|false $convertSpacesTo
     * @param array      $allowedCharacters
     *
     * @return string
     */
    public static function alphanum($value, $urldecode = false, $convertSpacesTo = false, $allowedCharacters = array())
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        if ($convertSpacesTo) {
            $value = str_replace(' ', $convertSpacesTo, $value);
            $allowedCharacters[] = $convertSpacesTo;
        }

        if (!empty($allowedCharacters)) {
            $regex = "/[^0-9a-z".implode('', $allowedCharacters)."]+/i";
        } else {
            $regex = "/[^0-9a-z]+/i";
        }

        return trim(preg_replace($regex, "", $value));
    }

    /**
     * Returns raw value
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
     * Removes all characters except those allowed in URLs
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
    public static function url($value, $urldecode = false, $allowedProtocols = null, $defaultProtocol = null, $removeQuery = array(), $ignoreFragment = false)
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        if (empty($allowedProtocols)) {
            $allowedProtocols = array('https', 'http', 'ftp');
        }
        if (empty($defaultProtocol)) {
            $defaultProtocol = 'http';
        }

        $value = filter_var($value, FILTER_SANITIZE_URL);
        $parts = parse_url($value);

        if ($parts) {
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
                (!empty($parts["scheme"])   ? $parts["scheme"]."://" :"") .
                (!empty($parts["user"])     ? $parts["user"].":"     :"") .
                (!empty($parts["pass"])     ? $parts["pass"]."@"     :"") .
                (!empty($parts["host"])     ? $parts["host"]         :"") .
                (!empty($parts["port"])     ? ":".$parts["port"]     :"") .
                (!empty($parts["path"])     ? $parts["path"]         :"") .
                (!empty($parts["query"])    ? "?".$parts["query"]    :"") .
                (!$ignoreFragment && !empty($parts["fragment"]) ? "#".$parts["fragment"] :"");
        } else {
            //must have a really bad URL since parse_url returned false so let's just clean it
            $value = self::clean($value);
        }

        //since a URL allows <>, let's add a safety step to remove <script> tags
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);

        return $value;
    }

    /**
     * Removes all characters except those allowed in emails
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
     * Returns a clean array
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
            $value = array($value);
        }

        return $value;
    }

    /**
     * Returns clean HTML
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
            $doctypeFound = preg_match("/(<!DOCTYPE(.*?)>)/is", $value, $doctype);

            // Special handling for CDATA tags
            $value = str_replace(array('<![CDATA[', ']]>'), array('<mcdata>', '</mcdata>'), $value, $cdataCount);

            // Special handling for conditional blocks
            $value = preg_replace("/<!--\[if(.*?)\]>(.*?)<!\[endif\]-->/is", '<mcondition><mif>$1</mif>$2</mcondition>', $value, -1, $conditionsFound);

            // Special handling for HTML comments
            $value = str_replace(array('<!--', '-->'), array('<mcomment>', '</mcomment>'), $value, $commentCount);

            $value = self::getFilter(true)->clean($value, 'html');

            // Was a doctype found?
            if ($doctypeFound) {
                $value = "$doctype[0]\n$value";
            }

            if ($cdataCount) {
                $value = str_replace(array('<mcdata>', '</mcdata>'), array('<![CDATA[', ']]>'), $value);
            }

            if ($conditionsFound) {
                // Special handling for conditional blocks
                $value = preg_replace("/<mcondition><mif>(.*?)<\/mif>(.*?)<\/mcondition>/is", '<!--[if$1]>$2<![endif]-->', $value);
            }

            if ($commentCount) {
                $value = str_replace(array('<mcomment>', '</mcomment>'), array('<!--', '-->'), $value   );
            }
        }

        return $value;
    }

    /**
     * Converts UTF8 into Latin
     *
     * @param $value
     *
     * @return mixed
     */
    public static function transliterate($value)
    {
        return \URLify::transliterate($value);
    }
}
