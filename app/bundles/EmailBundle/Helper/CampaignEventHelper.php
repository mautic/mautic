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
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;

class CampaignEventHelper
{

    /**
     * Determine if this campaign applies
     *
     * @param CampaignLeadChangeEvent $passthrough
     * @param $event
     *
     * @return bool
     */
    public static function validateEmailTrigger(Email $passthrough = null, $event)
    {
        if ($passthrough == null) {
            return true;
        }

        //check to see if the parent event is a "send email" event and that it matches the current email opened
        if (
            empty($event['parent']) ||
            $event['parent']['type'] != 'email.send' ||
            $passthrough->getId() != $event['parent']['type']['properties']['email']
        ) {
            return false;
        }

        /*
        $limitToEmails = $event['properties']['email'];

        //check against selected emails
        if (!empty($limitToEmails) && !in_array($passthrough->getId(), $limitToEmails)) {
            return false;
        }
        */
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
                'id' 		=> $lead->getId(),
                'email' 	=> $fields['core']['email']['value'],
                'firstname' => $fields['core']['firstname']['value'],
                'lastname' 	=> $fields['core']['lastname']['value']
            );
        } else {
            $leadCredentials = $lead;
        }

        if (!empty($leadCredentials['email'])) {
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $factory->getModel('email');

            $emailId = $event['properties']['email'];
            $email   = $emailModel->getEntity($emailId);

            if ($email != null) {

                $emailModel->sendEmail($email, array($leadCredentials['id'] => $leadCredentials));
                $emailSent = true;
            }
        }

        return $emailSent;
    }
}