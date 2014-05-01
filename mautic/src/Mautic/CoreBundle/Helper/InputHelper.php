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
     * Strips tags and trims value
     *
     * @param $value
     * @return string
     */
    static public function clean($value)
    {
        return trim(strip_tags($value));
    }
}