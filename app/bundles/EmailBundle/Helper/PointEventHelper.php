<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent;

/**
 * Class PointEventHelper
 */
class PointEventHelper
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

    /**
     * @param       $action
     *
     * @return array
     */
    public static function sendEmail(TriggerEvent $event, Lead $lead, MauticFactory $factory)
    {
        $properties = $event->getProperties();
        $emailId    = $properties['email'];
        $trigger    = $event->getTrigger();

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $factory->getModel('email');
        $email = $model->getEntity($emailId);

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {
            $leadFields = $lead->getFields();
            if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {
                $leadCredentials = array(
                    'email'     => $leadFields['core']['email']['value'],
                    'id'        => $lead->getId(),
                    'firstname' => $leadFields['core']['firstname']['value'],
                    'lastname'  => $leadFields['core']['lastname']['value']
                );
                $model->sendEmail($email, array($leadCredentials['id'] => $leadCredentials), array('trigger', $trigger->getId()));
            }
        }
    }
}