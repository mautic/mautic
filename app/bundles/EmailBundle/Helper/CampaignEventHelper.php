<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;

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
    public static function validateEmailTrigger(Email $passthrough, $event)
    {
        $limitToEmails = $event['properties']['email'];

        //check against selected emails
        if (!empty($limitToEmails) && !in_array($passthrough->getId(), $limitToEmails)) {
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

        if (!empty($lead['email'])) {
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $factory->getModel('email');
            $emailId = $event['properties']['email'];
            $email = $emailModel->getEntity($emailId);

            if ($email != null) {
                $emailModel->sendEmail($email, $lead);
                $emailSent = true;
            }
        }

        return $emailSent;
    }
}