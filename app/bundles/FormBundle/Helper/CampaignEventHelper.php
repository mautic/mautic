<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Entity\Form;

class CampaignEventHelper
{

    /**
     * Determine if this campaign applies
     *
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function validateFormSubmit(Form $eventDetails = null, $event)
    {
        if ($eventDetails == null) {
            return true;
        }

        $limitToForms = $event['properties']['forms'];

        //check against selected forms
        if (!empty($limitToForms) && !in_array($eventDetails->getId(), $limitToForms)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if this campaign applies
     *
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function validateFormValue(Form $eventDetails = null, $event)
    {
        if ($event['properties']['value'])
        \Doctrine\Common\Util\Debug::dump($eventDetails);
        \Doctrine\Common\Util\Debug::dump($event);
        die;
        return true;
    }
}
