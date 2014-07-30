<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

class AbTestHelper
{

    /**
     * Determines the winner of A/B test based on bounce rates
     *
     * @param $factory
     * @param $page
     * @param $properties
     */
    public static function determineBounceTestWinner($factory, $page, $properties)
    {
        //find the hits that did not go any further

    }

    /**
     * Determines the winner of A/B test based on dwell time rates
     *
     * @param $factory
     * @param $page
     * @param $properties
     */
    public static function determineDwellTimeTestWinner($factory, $page, $properties)
    {

    }
}