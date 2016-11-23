<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class DeprecatedTokenHelper
 */
class DeprecatedTokenHelper
{

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'token_helper';
    }

    public function getSafeRegex($fieldPrefix) {
        return '/({|%7B)'.$fieldPrefix.'=(.*?)(}|%7D)/';
    }

    /**
     * @param string $fieldPrefix
     * @param string $content
     * @param array  $fields
     * @param bool   $replace If true, search/replace will be executed
     *                        on $content and the modified $content
     *                        returned rather than an array of found
     *                        matches
     *
     * @return array|string
     */
    public function findTokens($fieldPrefix, $content, $fields, $replace = false)
    {
        // Search for bracket or bracket encoded
        $regex     = $this->getSafeRegex($fieldPrefix);
        $tokenList = array();
        $matches   = array();

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

                $value             = (!empty($fields[$alias])) ? $fields[$alias] : $fallback;
                $tokenList[$token] = ($urlencode) ? urlencode($value) : $value;
            }

            if ($replace) {
                $content = str_replace(array_keys($tokenList), $tokenList, $content);
            }
        }

        return $replace ? $content : $tokenList;
    }

}
