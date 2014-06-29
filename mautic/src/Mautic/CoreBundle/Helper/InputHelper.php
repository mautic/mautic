<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class InputHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class InputHelper
{

    /**
     * Wrapper function to clean inputs.  $mask can be an array of keys as the field names and values as the cleaning
     * function to be used for the specific field.
     *
     * @param mixed $value
     * @param mixed $mask
     * @return mixed
     */
    static function _($value, $mask = 'clean') {
        if (is_array($value) && is_array($mask)) {
            foreach ($value as $k => &$v) {
                if (array_key_exists($k, $mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask[$k])) {
                    $v = self::$mask[$k]($v);
                } else {
                    $v = self::clean($v);
                }
            }
            return $value;
        } elseif (is_string($mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
            return self::$mask($value);
        } else {
            return self::clean($value);
        }
    }


    /**
     * Cleans value by HTML-escaping '"<>& and characters with ASCII value less than 32
     *
     * @param $value
     * @return string
     */
    static public function clean($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::clean($v);
            }
            return $value;
        } else {
            return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    /**
     * Strips tags
     *
     * @param $value
     * @return mixed
     */
    static public function string($value)
    {
        return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    /**
     * Strips non-alphanumeric characters
     *
     * @param $value
     * @return string
     */
    static public function alphanum($value, $convertSpacesToHyphen = false)
    {
        if ($convertSpacesToHyphen) {
            $value = str_replace(' ', '-', $value);
            return trim(preg_replace("/[^0-9a-z-]+/i", "", $value));
        } else {
            return trim(preg_replace("/[^0-9a-z]+/i", "", $value));
        }
    }

    /**
     *
     */

    /**
     * Returns raw value
     *
     * @param $value
     * @return mixed
     */
    static public function raw($value)
    {
        return $value;
    }

    /**
     * Returns float value
     *
     * @param $value
     * @return float
     */
    static public function float($value)
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
    }

    /**
     * Returns int value
     *
     * @param $value
     * @return int
     */
    static public function int($value)
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Returns boolean value
     *
     * @param $value
     * @return bool
     */
    static public function boolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Removes all characters except those allowed in URLs
     *
     * @param $value
     * @param $allowedProtocols
     * @return mixed
     */
    static public function url($value, $allowedProtocols = null, $defaultProtocol = null, $removeQuery = array())
    {

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
                    if (isset($query[$q]))
                        unset($query[$q]);
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
                (!empty($parts["fragment"]) ? "#".$parts["fragment"] :"");
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
     * @param $value
     * @return mixed
     */

    static public function email($value)
    {
        $value = substr($value, 0, 254);
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Returns a clean array
     *
     * @param $value
     * @return array|string
     */
    static public function cleanArray($value)
    {
        $value = self::clean($value);
        if (!is_array($value)) {
            $value = array($value);
        }
        return $value;
    }

    /**
     * Clean HTML using htmLawed
     *
     * @param $element
     * @param $attribute_array
     * @return string
     */
    static public function html($value)
    {
        require_once __DIR__ . '/../Libraries/htmLawed/htmLawed.php';
        $config = array('tidy' => 4, 'hook_tag' => 'InputHelper::element');
        $value = htmLawed($value, $config);
        return $value;
    }

    /**
     * Cleans input sent from htmLawed
     *
     * @param $element
     * @param $attribute_array
     * @return string
     */
    static public function element($element, $attribute_array)
    {
        static $allowedElements = array('a', 'span', 'p', 'img', 'ul', 'ol', 'li');
        $badMatch = "`[^\w\s;:,#\-']`";
        static $allowedProperties = array('border', 'color', 'display', 'float', 'font-family', 'font-size', 'list-style-type', 'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'text-align', 'text-decoration', 'vertical-align');

        if (in_array($element, $allowedElements) && isset($attribute_array['style']) && !preg_match($badMatch, $attribute_array['style'])) {

            $style = $attribute_array['style'];
            $style = str_replace(array("\r", "\n", "\t"), '', $style);

            $properties      = explode(';', $style);
            $finalProperties = array();
            foreach ($properties as $namevalue) {
                $namevalue = explode(':', trim($namevalue));
                $name      = strtolower(trim($namevalue[0]));
                $value     = isset($namevalue[1]) ? $namevalue[1] : 0;
                if ($value and in_array($name, $allowedProperties)) {

                    $value = trim($value);
                    switch ($name) {
                        case 'border':
                            if (stripos('solid black', $value)) {
                                $finalProperties[] = 'border: ' . $value;
                            }
                            break;
                        case 'color':
                        case 'margin-top':
                        case 'margin-bottom':
                            $finalProperties[] = $name . ': ' . $value;
                            break;
                        case 'display':
                            if (stripos(' block', $value)) {
                                $finalProperties[] = 'display: ' . $value;
                            }
                            break;
                        case 'float':
                            if (stripos(' left right', $value)) {
                                $finalProperties[] = 'float: ' . $value;
                            }
                            break;
                        case 'font-size':
                            if (stripos(' xx-small medium large xx-large', $value)) {
                                $finalProperties[] = 'font-size: ' . $value;
                            }
                            break;
                        case 'font-family':
                            $fonts      = explode(',', $value);
                            $finalFonts = array();
                            foreach ($fonts as $font) {
                                $font = trim(strtolower($font), " '\"");
                                if (in_array($font, array('andale mono', 'arial', 'arial black', 'avant garde', 'chicago', 'comic sans ms', 'courier', 'courier new', 'geneva', 'georgia', 'helvetica', 'impact', 'monaco', 'tahoma', 'terminal', 'times', 'times new roman', 'trebuchet ms', 'verdana', 'serif', 'san-serif'))) {
                                    $finalFonts[] = $font;
                                }
                            }
                            if (!empty($finalFonts)) {
                                $finalProperties[] = 'font-family: ' . implode(', ', $finalFonts);
                            }
                            break;
                        case 'list-style-type':
                            if (stripos(' circle disc square lower-roman upper-roman lower-greek upper-greek lower-alpha upper-alpha', $value)) {
                                $finalProperties[] = 'list-style-type: ' . $value;
                            }
                            break;
                        case 'margin-left':
                        case 'margin-right':
                            if ((strtolower($value) == 'auto') or (preg_match('`(\d+)\s*px`i', $value, $m) and intval($m[1]) < 601)) {
                                $finalProperties[] = $name . ': ' . $value;
                            }
                            break;
                        case 'text-align':
                            if (stripos(' left right center justify', $value)) {
                                $finalProperties[] = 'text-align: ' . $value;
                            }
                            break;
                        case 'text-decoration':
                            if (strtolower($value) == 'underline') {
                                $finalProperties[] = 'text-decoration: ' . $value;
                            }
                            break;
                        case 'vertical-align':
                            if (stripos(' middle, bottom, top, baseline, text-top, text-bottom', $value)) {
                                $finalProperties[] = 'vertical-align: ' . $value;
                            }
                            break;
                    }
                }
            }
            $style = implode('; ', $finalProperties);
            if (!empty($style)) {
                $attribute_array['style'] = $style;
            } else {
                unset($attribute_array['style']);
            }
        } elseif (isset($attribute_array['style'])) {
            unset($attribute_array['style']);
        }

        $attributes = '';
        foreach ($attribute_array as $k => $v) {
            $attributes .= " {$k}=\"{$v}\"";
        }
        static $empty_elements = array('area' => 1, 'br' => 1, 'col' => 1, 'embed' => 1, 'hr' => 1, 'img' => 1, 'input' => 1, 'isindex' => 1, 'param' => 1);
        return "<{$element}{$attributes}" . (isset($empty_elements[$element]) ? ' /' : '') . '>';
    }
}