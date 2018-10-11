<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

class TokenAttribute
{
    /** @var string */
    private $attribute;

    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        $fallbackCheck = explode('|', $this->attribute);

        return $fallbackCheck[0];
    }

    /**
     * @return string
     */
    public function getModifier()
    {
        $fallbackCheck = explode('|', $this->attribute);
        if (!isset($fallbackCheck[1])) {
            return '';
        }

        return $fallbackCheck[1];
    }
}
