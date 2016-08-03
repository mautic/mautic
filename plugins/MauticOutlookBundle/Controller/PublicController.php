<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOutlookBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\EmailBundle\Swiftmailer\Transport\InterfaceCallbackTransport;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{

    /**
     * @return Response
     */
    public function trackingImageAction()
    {
        // if additional data were sent with the tracking pixel
        if ($this->request->server->get('QUERY_STRING')) {
            parse_str($this->request->server->get('QUERY_STRING'), $query);

            // URL attr 'd' is encoded so let's decode it first.
            if (isset($query['d'])) {
                // parse_str auto urldecodes
                $b64 = base64_decode($query['d']);
                $gz = gzdecode($b64);
                parse_str($gz, $query);
            }

            if (!empty($query) && isset($query['email']) && isset($query['subject']) && isset($query['body'])) {

                /** @var \Mautic\EmailBundle\Model\EmailModel $model */
                $model = $this->getModel('email');

                $idHash = hash('crc32', $query['body']);
                $idHash = substr($idHash . $idHash, 0, 13); // 13 bytes length

                $stat = $model->getEmailStatus($idHash);

                // sytat doesn't exist, create one
                if ($stat === null) {

                    // email is a semicolon delimited list of emails
                    $emails = explode(';', $query['email']);

                    $lead = null;
                    $to = '';

                    foreach ($emails as $email) {
                        $lead = $this->getModel('lead')->getRepository()->getLeadByEmail($email);
                        if ($lead !== null) {
                            $to = $email;
                            break;
                        }
                    }

                    if ($lead !== null) {
                        $mailer = $this->factory->getMailer();

                        // To lead
                        $mailer->addTo($to);

                        $mailer->setFrom($query['from'], '');

                        // Set Content
                        BuilderTokenHelper::replaceVisualPlaceholdersWithTokens($query['body']);
                        $mailer->setBody($query['body']);
                        $mailer->parsePlainText($query['body']);

                        // Set lead
                        $mailer->setLead($lead);
                        $mailer->setIdHash($idHash);

                        $mailer->setSubject($query['subject']);

                        // Ensure safe emoji for notification
                        $subject = EmojiHelper::toHtml($query['subject']);
                        $mailer->createEmailStat();
                    }

                }

                $model->hitEmail($idHash, $this->request);
            }
        }

        return TrackingPixelHelper::getResponse($this->request);
    }

}
