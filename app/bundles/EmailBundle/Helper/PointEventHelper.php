<?php

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;

class PointEventHelper
{
    public function __construct(private EmailModel $emailModel)
    {
    }

    public static function validateEmail($eventDetails, $action): bool
    {
        if (null === $eventDetails) {
            return false;
        }

        $emailId = $eventDetails->getId();

        if (isset($action['properties']['emails'])) {
            $limitToEmails = $action['properties']['emails'];
        }

        if (!empty($limitToEmails) && !in_array($emailId, $limitToEmails)) {
            // no points change
            return false;
        }

        return true;
    }

    public function sendEmail($event, Lead $lead): bool
    {
        $properties = $event['properties'];
        $emailId    = (int) $properties['email'];

        $email = $this->emailModel->getEntity($emailId);

        // make sure the email still exists and is published
        if (null != $email && $email->isPublished()) {
            $leadFields = $lead->getFields();
            if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {
                $leadCredentials       = $lead->getProfileFields();
                $leadCredentials['id'] = $lead->getId();

                $options   = ['source' => ['trigger', $event['id']]];
                $emailSent = $this->emailModel->sendEmail($email, $leadCredentials, $options);

                return is_array($emailSent) ? false : true;
            }
        }

        return false;
    }
}
