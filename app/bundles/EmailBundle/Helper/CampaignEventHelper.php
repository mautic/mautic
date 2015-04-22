<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;

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
    public static function validateEmailTrigger(Email $eventDetails = null, $event)
    {
        if ($eventDetails == null) {
            return true;
        }

        //check against selected emails
        $limitToEmails = $event['properties']['emails'];

        //check to see if the parent event is a "send email" event and that it matches the current email opened
        if (empty($limitToEmails) && !empty($event['parent']) && $event['parent']['type'] == 'email.send') {
            return ($eventDetails->getId() === (int) $event['parent']['properties']['email']);
        } elseif (!empty($limitToEmails) && !in_array($eventDetails->getId(), $limitToEmails)) {
            return false;
        }

        return true;
    }

    /**
     * @param MauticFactory $factory
     * @param               $lead
     * @param               $event
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public static function sendEmailAction(MauticFactory $factory, $lead, $event)
    {
        $emailSent = false;

        if ($lead instanceof Lead) {
            $fields = $lead->getFields();

            $leadCredentials = array(
                'id'        => $lead->getId(),
                'email'     => $fields['core']['email']['value'],
                'firstname' => $fields['core']['firstname']['value'],
                'lastname'  => $fields['core']['lastname']['value']
            );
        } else {
            $leadCredentials = $lead;
        }

        if (!empty($leadCredentials['email'])) {
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $factory->getModel('email');

            $emailId = (int) $event['properties']['email'];

            $email = $emailModel->getEntity($emailId);

            if ($email != null && $email->isPublished()) {
                $options   = array('source' => array('campaign', $event['campaign']['id']));
                $emailSent = $emailModel->sendEmail($email, array($leadCredentials['id'] => $leadCredentials), $options);
            }
        }

        unset($lead, $leadCredentials, $email, $emailModel, $factory);

        return $emailSent;
    }
}