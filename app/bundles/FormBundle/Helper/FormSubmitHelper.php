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

        if (!empty($config['to'])) {
            $emails = explode(',', $config['to']);
            foreach ($emails as $e) {
                $mailer->message->addTo($e);
            }
        }

        if ($config['copy_lead']) {
            $fields = $lead->getFields();
            $email = $fields['core']['email']['value'];
            if (!empty($email)) {
                $mailer->message->addto($email);
            }
        }

        if (!empty($config['cc'])) {
            $emails = explode(',', $config['cc']);
            foreach ($emails as $e) {
                $mailer->message->addCc($e);
            }
        }

        if (!empty($config['bcc'])) {
            $emails = explode(',', $config['bcc']);
            foreach ($emails as $e) {
                $mailer->message->addBcc($e);
            }
        }

        $mailer->message->setSubject($config['subject']);

        $message = str_ireplace($tokens['search'], $tokens['replace'], $config['message']);
        $mailer->message->setBody($message, 'text/html');
        $mailer->parsePlainText($message);

        $mailer->send();
    }
}