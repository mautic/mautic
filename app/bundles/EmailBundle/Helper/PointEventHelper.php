<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class PointEventHelper.
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
        if (null === $eventDetails) {
            return false;
        }

        if ($eventDetails instanceof Stat) {
            $eventDetails = $eventDetails->getEmail();
        }

        $emailId = $eventDetails->getId();

        if (isset($action['properties']['emails'])) {
            $limitToEmails = $action['properties']['emails'];
        }

        if (!empty($limitToEmails) && !in_array($emailId, $limitToEmails)) {
            //no points change
            return false;
        }

        return true;
    }

    /**
     * @param $eventDetails
     * @param $action
     *
     * @return bool
     */
    public function validateEmailByOpen($eventDetails, $action)
    {
        if (!self::validateEmail($eventDetails, $action)) {
            return false;
        }

        if (!$eventDetails instanceof Stat) {
            return true;
        }

        // true If I it'đ repeatble or execute_each is disabled
        if (empty($action['properties']['execute_each']) || !empty($action['repeatable'])) {
            return true;
        }

        // already opened
        if ($eventDetails->getOpenCount() > 1) {
            return false;
        }

        return true;
    }

    /**
     * @param               $event
     * @param Lead          $lead
     * @param MauticFactory $factory
     *
     * @return bool
     */
    public static function sendEmail($event, Lead $lead, MauticFactory $factory)
    {
        $properties = $event['properties'];
        $emailId    = (int) $properties['email'];

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $factory->getModel('email');
        $email = $model->getEntity($emailId);

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {
            $leadFields = $lead->getFields();
            if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {
                /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
                $leadModel             = $factory->getModel('lead');
                $leadCredentials       = $leadModel->flattenFields($leadFields);
                $leadCredentials['id'] = $lead->getId();

                $options   = ['source' => ['trigger', $event['id']]];
                $emailSent = $model->sendEmail($email, $leadCredentials, $options);

                return is_array($emailSent) ? false : true;
            }
        }

        return false;
    }
}
