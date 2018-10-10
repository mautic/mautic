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
 * abstract Replacer.
 */
abstract class Replacer implements ReplacerInterface
{
    /**
     * @param $content
     * @param array|string $regex
     *
     * @return array
     */
    public function findTokens($content, $regex)
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
                    $tokens[$token] = new Match($match);
                }
            }
        }

        return $tokens;
    }
}
