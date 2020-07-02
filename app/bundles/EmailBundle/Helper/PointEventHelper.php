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
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class PointEventHelper.
 */
class PointEventHelper
{
    /**
     * @param Email $eventDetails
     * @param $action
     *
     * @return int
     */
    public static function validateEmail($eventDetails, $action)
    {
        if (null === $eventDetails) {
            return false;
        }

        $emailId = $eventDetails->getId();

        if (!empty($action['properties']['emails'])) {
            $limitToEmails = $action['properties']['emails'];
            if (!in_array($emailId, $limitToEmails)) {
                //no points change
                return false;
            }
        }

        if (!empty($action['properties']['categories'])) {
            $categoryId        = $eventDetails->getCategory() ? $eventDetails->getCategory()->getId() : null;
            $limitToCategories = $action['properties']['categories'];
            if (!in_array($categoryId, $limitToCategories)) {
                //no points change
                return false;
            }
        }

        return true;
    }

    /**
     * @param $event
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
        if (null != $email && $email->isPublished()) {
            $leadFields = $lead->getFields();
            if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {
                /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
                $leadCredentials       = $lead->getProfileFields();
                $leadCredentials['id'] = $lead->getId();

                $options   = ['source' => ['trigger', $event['id']]];
                $emailSent = $model->sendEmail($email, $leadCredentials, $options);

                return is_array($emailSent) ? false : true;
            }
        }

        return false;
    }
}
