<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

/**
 * abstract TokenReplacer.
 */
abstract class TokenReplacer implements TokenReplacerInterface
{
    /**
     * @param string          $content
     * @param array|Lead|null $contact
     *
     * @return string
     */
    public function replaceTokens($content, $contact)
    {
        $tokenList = $this->getTokens($content, $contact);

        return str_replace(array_keys($tokenList), $tokenList, $content);
    }

    /**
     * @param $content
     * @param array|string $regex
     *
     * @return array
     */
    public function searchTokens($content, $regex)
    {
        // convert string to array
        if (!is_array($regex)) {
            $regex = [$regex];
        }
        $tokens = [];
        foreach ($regex as $regx) {
            preg_match_all($regx, $content, $matches);
            if (isset($matches[2])) {
                foreach ($matches[2] as $key => $match) {
                    $token = $matches[0][$key];

                    if (isset($tokens[$token])) {
                        continue;
                    }
                    $tokens[$token] = new TokenAttribute($match);
                }
            }
        }

        return $tokens;
    }
}
