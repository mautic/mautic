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
        // replace line brakes with <br> for textarea values
        if ($tokens) {
            foreach ($tokens as $token => &$value) {
                $value = nl2br(html_entity_decode($value));
            }
        }

        $mailer = $factory->getMailer();
        $emails = (!empty($config['to'])) ? array_fill_keys(explode(',', $config['to']), null) : array();

        $mailer->setTo($emails);

        $leadEmail = $lead->getEmail();

        if (!empty($leadEmail)) {
            // Reply to lead for user convenience
            $mailer->setReplyTo($leadEmail);
        }

        if (!empty($config['cc'])) {
            $emails = array_fill_keys(explode(',', $config['cc']), null);
            $mailer->setCc($emails);
        }

        if (!empty($config['bcc'])) {
            $emails = array_fill_keys(explode(',', $config['bcc']), null);
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