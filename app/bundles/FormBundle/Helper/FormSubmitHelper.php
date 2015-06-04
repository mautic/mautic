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
use Mautic\FormBundle\Entity\Action;

class FormSubmitHelper
{
	/**
     * @param       $action
     *
     * @return array
     */
    public static function sendEmail($tokens, $config, MauticFactory $factory, $lead)
    {
        $mailer = $factory->getMailer();
        $emails = (!empty($config['to'])) ? explode(',', $config['to']) : array();

        $fields = $lead->getFields();
        $email  = $fields['core']['email']['value'];

        if (!empty($email)) {
            if ($config['copy_lead']) {
                $emails[] = $email;
            }

            $mailer->setReplyTo($email);
        }

        $mailer->setTo($emails);

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
    }
}