<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;

class FormSubmitHelper
{
    /**
     * @param               $tokens
     * @param               $config
     * @param MauticFactory $factory
     * @param Lead          $lead
     */
    public static function sendEmail($tokens, $config, MauticFactory $factory, Lead $lead)
    {
        $mailer = $factory->getMailer();
        $emails = (!empty($config['to'])) ? explode(',', $config['to']) : array();

        $mailer->setTo($emails);

        $leadEmail = $lead->getEmail();
        if (!empty($leadEmail)) {
            // Reply to lead for user convenience
            $mailer->setReplyTo($leadEmail);
        }

        if (!empty($config['cc'])) {
            $emails = explode(',', $config['cc']);
            $mailer->setCc($emails);
        }

        if (!empty($config['bcc'])) {
            $emails = explode(',', $config['bcc']);
            $mailer->setBcc($emails);
        }

        $mailer->setSubject($config['subject']);

        $mailer->setTokens($tokens);
        $mailer->setBody($config['message']);
        $mailer->parsePlainText($config['message']);

        $mailer->send();

        if ($config['copy_lead'] && !empty($leadEmail)) {
            // Send copy to lead
            $mailer->reset();

            $mailer->setTo($leadEmail);

            $mailer->setSubject($config['subject']);
            $mailer->setTokens($tokens);
            $mailer->setBody($config['message']);
            $mailer->parsePlainText($config['message']);

            $mailer->send();
        }
    }
}