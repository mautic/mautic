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

/**
 * Helper class for Emoji unicodes.
 *
 * Build from modified https://github.com/iamcal/php-emoji
 */
class EmojiHelper
{
    /**
     * Convert to html.
     *
     * @param $text
     * @param $from
     *
     * @return mixed
     */
    public static function toHtml($text, $from = 'emoji')
    {
        return self::emojiConvert($text, $from, 'html');
    }

    /**
     * Convert to emoji.
     *
     * @param $text
     * @param $from
     *
     * @return mixed
     */
    public static function toEmoji($text, $from = 'html')
    {
        return self::emojiConvert($text, $from, 'emoji');
    }

    /**
     * Convert to short code.
     *
     * @param        $text
     * @param string $from
     *
     * @return mixed
     */
    public static function toShort($text, $from = 'emoji')
    {
        return self::emojiConvert($text, $from, 'short');
    }

    /**
     * Converts emojis.
     *
     * @param $text
     * @param $from
     * @param $to
     *
     * @return mixed
     */
    private static function emojiConvert($text, $from, $to)
    {
        $maps = [];
        switch ($from) {
            case 'html':
                switch ($to) {
                    case 'emoji':
                        $maps[] = 'HtmlToUnicode';
                        break;
                    case 'short':
                        $maps[] = 'HtmlToUnicode';
                        $maps[] = 'UnicodeToShort';
                        break;
                }
                break;
            case 'emoji':
                switch ($to) {
                    case 'html':
                        $maps[] = 'UnicodeToHtml';
                        break;
                    case 'short':
                        $maps[] = 'UnicodeToShort';
                        break;
                }
                break;
            case 'short':
                switch ($to) {
                    case 'html':
                        $maps[] = 'ShortToUnicode';
                        $maps[] = 'UnicodeToHtml';
                        break;
                    case 'emoji':
                        $maps[] = 'ShortToUnicode';
                        break;
                }
                break;
        }

        foreach ($maps as $useMap) {
            $mapClass = "Mautic\\CoreBundle\\Helper\\EmojiMap\\{$useMap}EmojiMap";
            $text     = str_replace(array_keys($mapClass::$map), $mapClass::$map, $text);

            if (isset($mapClass::$exceptions)) {
                $text = str_replace(array_keys($mapClass::$exceptions), $mapClass::$exceptions, $text);
            }
        }

        if ($to !== 'emoji') {
            // Parse out missed emojis
            $text = self::removeEmoji($text);
        }

        return $text;
    }

    /**
     * Remove emojis from text.
     *
     * @param $text
     *
     * @return mixed
     */
    private static function removeEmoji($text)
    {
        return preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }
}
