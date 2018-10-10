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
 * CLass Match.
 */
class Match
{
    private $match;

    /**
     * Token constructor.
     *
     * @param $match
     */
    public function __construct($match)
    {
        $this->match = $match;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        $fallbackCheck = explode('|', $this->match);

        return $fallbackCheck[0];
    }

    /**
     * @return string
     */
    public function getModifier()
    {
        $fallbackCheck = explode('|', $this->match);
        if (!isset($fallbackCheck[1])) {
            return '';
        }

        return $fallbackCheck[1];
    }
}
