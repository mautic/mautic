<?php

namespace Mautic\CoreBundle\Helper;

use Joomla\Filter\InputFilter;

class InputHelper
{
    /**
     * String filter.
     */
    private static ?InputFilter $stringFilter = null;

    /**
     * HTML filter.
     */
    private static ?InputFilter $htmlFilter = null;

    private static ?InputFilter $strictHtmlFilter = null;

    /**
     * Adjust the boolean values from text to boolean.
     * Do not convert null to false.
     * Do not convert invalid values to false, but return null.
     *
     * @param bool|int|string|null $value
     *
     * @return bool|null
     */
    public static function boolean($value)
    {
        return match (strtoupper((string) $value)) {
            'T', 'Y' => true,
            'F', 'N' => false,
            default => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        };
    }

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

        return match (true) {
            $html   => ($strict) ? self::$strictHtmlFilter : self::$htmlFilter,
            default => self::$stringFilter,
        };
    }

    /**
     * Wrapper to InputHelper.
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
                        } elseif (method_exists(self::class, $mask[$k])) {
                            $useMask = $mask[$k];
                        }
                    } elseif (is_array($v)) {
                        // Likely a collection so use the same mask
                        $useMask = $mask;
                    }
                } elseif (method_exists(self::class, $mask)) {
                    $useMask = $mask;
                }

                if (is_array($v)) {
                    $v = self::_($v, $useMask, $urldecode);
                } elseif ('filter' === $useMask) {
                    $v = self::getFilter()->clean($v, $useMask);
                } elseif (null !== $v) {
                    $v = self::$useMask($v, $urldecode);
                }
            }

            return $value;
        } elseif (null === $value) {
            return $value;
        } elseif (is_string($mask) && method_exists(self::class, $mask)) {
            return self::$mask($value, $urldecode);
        } else {
            return self::getFilter()->clean($value, $mask);
        }
    }

    /**
     * Cleans value by HTML-escaping '"<>& and characters with ASCII value less than 32.
     *
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
     */
    public static function string(string $value, bool $urldecode = false): string
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        return self::filter_string_polyfill($value);
    }

    /**
     * Strips non-alphanumeric characters.
     *
     * @param string[] $allowedCharacters
     */
    public static function alphanum(string $value, bool $urldecode = false, ?string $convertSpacesTo = null, array $allowedCharacters = []): string
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

        return trim(preg_replace($regex, '', (string) $value));
    }

    /**
     * Returns a satnitized string which can be used in a file system.
     * Attaches the file extension if provided.
     *
     * @param string $value
     * @param string $extension
     *
     * @return string
     */
    public static function filename($value, $extension = null)
    {
        $value = str_replace(' ', '_', $value);

        $sanitized = preg_replace("/[^a-z0-9\.\_-]/", '', strtolower($value));
        $sanitized = preg_replace("/^\.\./", '', strtolower($sanitized));

        if (null === $extension) {
            return $sanitized;
        }

        return sprintf('%s.%s', $sanitized, $extension);
    }

    /**
     * Returns raw value.
     *
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
     * @param bool|false         $urldecode
     * @param array<string>|null $allowedProtocols
     * @param mixed              $defaultProtocol
     * @param array<string>      $removeQuery
     * @param bool|false         $ignoreFragment
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

        if (!$parts || !filter_var($value, FILTER_VALIDATE_URL)) {
            // This is a bad URL so just clean the whole thing and return it
            return self::clean($value);
        }

        $parts['scheme'] ??= $defaultProtocol;
        if (!in_array($parts['scheme'], $allowedProtocols)) {
            $parts['scheme'] = $defaultProtocol;
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);

            // remove specified keys from the query
            foreach ($removeQuery as $q) {
                if (isset($query[$q])) {
                    unset($query[$q]);
                }
            }

            // http_build_query urlencodes by default
            $parts['query'] = http_build_query($query);
        }

        return
            // already clean due to the exclusion list above
            (!empty($parts['scheme']) ? $parts['scheme'].'://' : '').
            // strip tags that could be embedded in the username or password
            (!empty($parts['user']) ? strip_tags($parts['user']).':' : '').
            (!empty($parts['pass']) ? strip_tags($parts['pass']).'@' : '').
            // should be caught by FILTER_VALIDATE_URL if the host has invalid characters
            (!empty($parts['host']) ? $parts['host'] : '').
            // type cast to int
            (!empty($parts['port']) ? ':'.(int) $parts['port'] : '').
            // strip tags that could be embedded in a path
            (!empty($parts['path']) ? strip_tags($parts['path']) : '').
            // cleaned through the parse_str (urldecode) and http_build_query (urlencode) above
            (!empty($parts['query']) ? '?'.$parts['query'] : '').
            // strip tags that could be embedded in the fragment
            (!$ignoreFragment && !empty($parts['fragment']) ? '#'.strip_tags($parts['fragment']) : '');
    }

    /**
     * Removes all characters except those allowed in emails.
     *
     * @param bool|false $urldecode
     */
    public static function email($value, $urldecode = false): string
    {
        if ($urldecode) {
            $value = urldecode($value);
        }

        $value = substr($value, 0, 254);
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        return trim($value);
    }

    /**
     * Returns a clean array.
     *
     * @param bool|false $urldecode
     *
     * @return array|mixed|string
     */
    public static function cleanArray($value, $urldecode = false)
    {
        $value = self::clean($value, $urldecode);

        // Return empty array for empty values
        if (empty($value)) {
            return [];
        }

        // Put a value into array if not an array
        if (!is_array($value)) {
            $value = [$value];
        }

        return $value;
    }

    /**
     * Returns clean HTML.
     *
     * @param string[]|string $value
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
            $doctypeFound = preg_match('/(<!DOCTYPE(.*?)>)/is', (string) $value, $doctype);
            // Special handling for CDATA tags
            $value = str_replace(['<![CDATA[', ']]>'], ['<mcdata>', '</mcdata>'], (string) $value, $cdataCount);
            // Special handling for conditional blocks
            preg_match_all("/<!--\[if(.*?)\]>(.*?)(?:\<\!\-\-)?<!\[endif\]-->/is", $value, $matches);
            if (!empty($matches[0])) {
                $from = [];
                $to   = [];
                foreach ($matches[0] as $key=>$match) {
                    $from[]   = $match;
                    $startTag = '<mcondition>';
                    $endTag   = '</mcondition>';
                    if (str_contains($match, '<!--<![endif]-->')) {
                        $startTag = '<mconditionnonoutlook>';
                        $endTag   = '</mconditionnonoutlook>';
                    }
                    $to[] = $startTag.'<mif>'.$matches[1][$key].'</mif>'.$matches[2][$key].$endTag;
                }
                $value = str_replace($from, $to, $value);
            }

            // Special handling for XML tags used in Outlook optimized emails <o:*/> and <w:/>
            $value = preg_replace_callback(
                "/<\/*[o|w|v]:[^>]*>/is",
                fn ($matches): string => '<mencoded>'.htmlspecialchars($matches[0]).'</mencoded>',
                $value, -1, $needsDecoding);

            // Special handling for script tags
            $value = preg_replace_callback(
                "/<script>(.*?)<\/script>/is",
                fn ($matches): string => '<mscript>'.base64_encode($matches[0]).'</mscript>',
                $value, -1, $needsScriptDecoding);

            // Special handling for HTML comments
            $value = str_replace(['<!-->', '<!--', '-->'], ['<mcomment></mcomment>', '<mcomment>', '</mcomment>'], $value, $commentCount);

            // Check for non-ASCII characters
            $hasUnicode = mb_check_encoding($value, 'UTF-8') && preg_match('/[^\x00-\x7F]/', $value);

            $value = self::getFilter(true)->clean($value, $hasUnicode ? 'raw' : 'html');

            // After cleaning encode the value
            $value = $hasUnicode ? rawurldecode($value) : $value;

            // Was a doctype found?
            if ($doctypeFound && false === $hasUnicode) {
                $value = "$doctype[0]$value";
            }

            if ($cdataCount) {
                $value = str_replace(['<mcdata>', '</mcdata>'], ['<![CDATA[', ']]>'], $value);
            }

            if (!empty($matches[0])) {
                // Special handling for conditional blocks
                $value = preg_replace("/<mconditionnonoutlook><mif>(.*?)<\/mif>(.*?)<\/mconditionnonoutlook>/is", '<!--[if$1]>$2<!--<![endif]-->', $value);
                $value = preg_replace("/<mcondition><mif>(.*?)<\/mif>(.*?)<\/mcondition>/is", '<!--[if$1]>$2<![endif]-->', $value);
            }

            if ($commentCount) {
                $value = str_replace(['<mcomment>', '</mcomment>'], ['<!--', '-->'], $value);
            }

            if ($needsDecoding) {
                $value = preg_replace_callback(
                    "/<mencoded>(.*?)<\/mencoded>/is",
                    fn ($matches): string => htmlspecialchars_decode($matches[1]),
                    $value);
            }

            if ($needsScriptDecoding) {
                $value = preg_replace_callback(
                    "/<mscript>(.*?)<\/mscript>/is",
                    fn ($matches): string => base64_decode($matches[1]),
                    $value);
            }
        }

        return $value;
    }

    /**
     * Allows tags 'b', 'i', 'u', 'em', 'strong', 'a', 'span'.
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
     */
    public static function transliterate($value): string|false
    {
        $transId = 'Any-Latin; Latin-ASCII';
        if (function_exists('transliterator_transliterate') && $trans = \Transliterator::create($transId)) {
            // Use intl by default
            return $trans->transliterate($value);
        }

        return \URLify::transliterate((string) $value);
    }

    public static function transliterateFilename(string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $filename = self::alphanum(self::transliterate($pathInfo['filename']), false, '-');
        if (isset($pathInfo['extension'])) {
            $filename .= '.'.$pathInfo['extension'];
        }

        return $filename;
    }

    public static function minifyHTML(string $html): string
    {
        if ('' === trim($html)) {
            return $html;
        }
        // Remove extra white-space(s) between HTML attribute(s)
        $html = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', fn ($matches): string => '<'.$matches[1].preg_replace(
            '#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s',
            ' $1$2',
            $matches[2]
        ).$matches[3].'>', str_replace("\r", '', $html));
        // Minify inline CSS declaration(s)
        if (str_contains($html, ' style=')) {
            $html = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', fn ($matches): string => '<'.$matches[1].' style='.$matches[2].self::minifyCss($matches[3]).$matches[2], $html);
        }

        $html = preg_replace(
            [
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s',
                // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
                // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
                // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s',
                // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s',
                // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#',
                // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#',
                // --ibid
            ],
            [
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
            ],
            $html
        );

        return str_replace(["\r", "\n"], ' ', $html);
    }

    private static function minifyCss(string $css): string
    {
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', preg_replace('/\s+/', ' ', $css));
        // Remove comments
        $css = preg_replace('/\/\*[^*]*\*+([^\/*][^*]*\*+)*\//', '', $css);
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove leading and trailing whitespace
        $css = trim($css);
        // Replace multiple semicolons with one
        $css = preg_replace('/;(?=;)/', '', $css);
        // Replace multiple whitespaces with one
        $css = preg_replace('/(\s+)/', ' ', $css);
        // Replace 0(px,em,%, etc) with 0
        $css = preg_replace('/(:| )0(\.\d+)?(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css);

        return $css;
    }

    /**
     * Needed to support PHP 8.1 without changing behavior.
     *
     * @see https://stackoverflow.com/questions/69207368/constant-filter-sanitize-string-is-deprecated
     */
    private static function filter_string_polyfill(string $string): string
    {
        return preg_replace('/\x00|<[^>]*>?/', '', $string);
    }
}
