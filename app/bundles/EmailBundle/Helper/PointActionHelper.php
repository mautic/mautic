<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

/**
 * Class PointActionHelper
 */
class PointActionHelper
{

    /**
     * @param $eventDetails
     * @param $action
     *
     * @return int
     */
    public static function validateEmail($eventDetails, $action)
    {
        $emailId       = $eventDetails->getId();
        $limitToEmails = $action['properties']['emails'];

        if (!empty($limitToEmails) && !in_array($emailId, $limitToEmails)) {
            //no points change
            return false;
        }

        return true;
    }
}