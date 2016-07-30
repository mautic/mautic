<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use TDM\SwiftMailerEventBundle\Events\MailerSendEvent;
use TDM\SwiftMailerEventBundle\Events\TransportSendEvent;
use TDM\SwiftMailerEventBundle\Model\SmtpTransport;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

    private $storedValues = array();

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_POST_SAVE    => array('onEmailPostSave', 0),
            EmailEvents::EMAIL_POST_DELETE  => array('onEmailDelete', 0),
            EmailEvents::EMAIL_FAILED       => array('onEmailFailed', 0),
            EmailEvents::EMAIL_ON_SEND      => array('onEmailSend', 0),
            EmailEvents::EMAIL_RESEND       => array('onEmailResend', 0),
            EmailEvents::EMAIL_PARSE        => array('onEmailParse', 0),
            EmailEvents::EMAIL_MAILER_PRE_SEND_PROCESS => array('onEmailMailerPreSendProcess', 0),
            EmailEvents::EMAIL_MAILER_PRE_SEND_CLEANUP => array('onEmailMailerPreSendCleanup', 0),
            EmailEvents::EMAIL_TRANSPORT_PRE_SEND_PROCESS => array('onEmailTransportPreSendProcess', 0),
            EmailEvents::EMAIL_TRANSPORT_PRE_SEND_CLEANUP => array('onEmailTransportPreSendCleanup', 0),
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\EmailEvent $event
     */
    public function onEmailPostSave(Events\EmailEvent $event)
    {
        $email = $event->getEmail();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "email",
                "object"    => "email",
                "objectId"  => $email->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->factory->getIpAddressFromRequest()
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\EmailEvent $event
     */
    public function onEmailDelete(Events\EmailEvent $event)
    {
        $email = $event->getEmail();
        $log = array(
            "bundle"     => "email",
            "object"     => "email",
            "objectId"   => $email->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $email->getName()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Process if an email has failed
     *
     * @param Events\QueueEmailEvent $event
     */
    public function onEmailFailed(Events\QueueEmailEvent $event)
    {
        $message = $event->getMessage();

        if (isset($message->leadIdHash)) {
            $model = $this->factory->getModel('email');
            $stat  = $model->getEmailStatus($message->leadIdHash);

            if ($stat !== null) {
                $reason = $this->factory->getTranslator()->trans('mautic.email.dnc.failed', array(
                    "%subject%" => EmojiHelper::toShort($message->getSubject())
                ));
                $model->setDoNotContact($stat, $reason);
            }
        }
    }

    /**
     * Add an unsubscribe email to the List-Unsubscribe header if applicable
     *
     * @param Events\EmailSendEvent $event
     */
    public function onEmailSend(Events\EmailSendEvent $event)
    {
        $helper = $event->getHelper();
        if ($helper && $unsubscribeEmail = $helper->generateUnsubscribeEmail()) {
            $headers          = $event->getTextHeaders();
            $existing         = (isset($headers['List-Unsubscribe'])) ? $headers['List-Unsubscribe'] : '';
            $unsubscribeEmail = "<mailto:$unsubscribeEmail>";
            $updatedHeader    = ($existing) ? $unsubscribeEmail.", ".$existing : $unsubscribeEmail;

            $event->addTextHeader('List-Unsubscribe', $updatedHeader);
        }
    }

    /**
     * Process if an email is resent
     *
     * @param Events\QueueEmailEvent $event
     */
    public function onEmailResend(Events\QueueEmailEvent $event)
    {
        $message = $event->getMessage();

        if (isset($message->leadIdHash)) {
            $model = $this->factory->getModel('email');
            $stat  = $model->getEmailStatus($message->leadIdHash);
            if ($stat !== null) {
                $stat->upRetryCount();

                $retries = $stat->getRetryCount();
                if (true || $retries > 3) {
                    //tried too many times so just fail
                    $reason = $this->factory->getTranslator()->trans('mautic.email.dnc.retries', array(
                        "%subject%" => EmojiHelper::toShort($message->getSubject())
                    ));
                    $model->setDoNotContact($stat, $reason);
                } else {
                    //set it to try again
                    $event->tryAgain();
                }

                $em = $this->factory->getEntityManager();
                $em->persist($stat);
                $em->flush();
            }
        }
    }

    /**
     * @param Events\ParseEmailEvent $event
     */
    public function onEmailParse(Events\ParseEmailEvent $event)
    {
        // Listening for bounce_folder and unsubscribe_folder
        $isBounce      = $event->isApplicable('EmailBundle', 'bounces');
        $isUnsubscribe = $event->isApplicable('EmailBundle', 'unsubscribes');

        if ($isBounce || $isUnsubscribe) {
            // Process the messages

            /** @var \Mautic\EmailBundle\Helper\MessageHelper $messageHelper */
            $messageHelper = $this->factory->getHelper('message');

            $messages = $event->getMessages();
            foreach ($messages as $message) {
                $messageHelper->analyzeMessage($message, $isBounce, $isUnsubscribe);
            }
        }
    }

    public function onEmailMailerPreSendProcess(MailerSendEvent $event) {
        /** @var MauticMessage $message */
        $message = $event->getMessage();
        if (!$message instanceof MauticMessage) {
            return;
        }

        // Get lead.
        $to = $message->getTo();
        $to = is_array($to) ? key($to) : $to;
        $leads = $this->factory->getModel('lead.lead')
            ->getRepository()
            ->getLeadsByFieldValue('email', $to);
        if (
            /** @var \Mautic\LeadBundle\Entity\Lead $lead */
            !$lead = reset($leads)
        ) {
            throw new \Exception("No lead with such email: $to");
        }

        // Get owner.
        if (!$owner = $lead->getOwner()) {
            // Then default transport used - no change needed.
            return;
        }

        // Get sender email and match SMTP credentials.
        switch ($owner->getEmail()) {
            case 'info@example.com':
            default:
                $message->setMetadata($to, 'AuthMode', 'login');
                $message->setMetadata($to, 'Encryption', 'ssl');
                $message->setMetadata($to, 'Host', 'smtp.example.com');
                $message->setMetadata($to, 'Password', 'secretPassword');
                $message->setMetadata($to, 'Port', 465);
                $message->setMetadata($to, 'UserName', 'info@example.com');
                $message->setFrom(array(
                    'info@example.com' => 'Example',
                ));
                break;
        }
    }

    public function onEmailMailerPreSendCleanup(MailerSendEvent $event) {
        /** @var MauticMessage $message */
        $message = $event->getMessage();
        if (!$message instanceof MauticMessage) {
            return;
        }
        // Remove any data you don't want to be serialized here.
        // For our purposes, we don't need to remove anything now.
    }

    public function onEmailTransportPreSendProcess(TransportSendEvent $event) {
        /** @var MauticMessage $message */
        $message = $event->getMessage();
        if (!$message instanceof MauticMessage) {
            return;
        }

        $transport = $event->getTransport();
        if (!$transport instanceof SmtpTransport) {
            return;
        }

        $to = $message->getTo();
        $to = is_array($to) ? key($to) : $to;
        // Make sure all settings are added to message.
        $metadata = $message->getMetadata();
        foreach (array('AuthMode', 'Encryption', 'Host', 'Password', 'Port', 'UserName') as $settingName) {
            if (empty($metadata[$to][$settingName])) {
                return;
            }
        }

        // Clear the stored values.
        $this->storedValues = array();

        // Make a copy of the existing values.
        $this->storedValues['AuthMode'] = $transport->getAuthMode();
        $this->storedValues['Encryption'] = $transport->getEncryption();
        $this->storedValues['Host'] = $transport->getHost();
        $this->storedValues['Password'] = $transport->getPassword();
        $this->storedValues['Port'] = $transport->getPort();
        $this->storedValues['UserName'] = $transport->getUsername();

        // Change the values to the settings.
        $transport->setAuthMode($metadata[$to]['AuthMode']);
        $transport->setEncryption($metadata[$to]['Encryption']);
        $transport->setHost($metadata[$to]['Host']);
        $transport->setPassword($metadata[$to]['Password']);
        $transport->setPort($metadata[$to]['Port']);
        $transport->setUsername($metadata[$to]['UserName']);

        // Restart (stop and start) the transport so it uses the new values
        // and connects to new server
        $transport->stop();
        $transport->start();
    }

    public function onEmailTransportPreSendCleanup(TransportSendEvent $event) {
        $transport = $event->getTransport();
        if (!$transport instanceof SmtpTransport) {
            return;
        }

        // Make sure all settings are available.
        foreach (array('AuthMode', 'Encryption', 'Host', 'Password', 'Port', 'UserName') as $settingName) {
            if (!array_key_exists($settingName, $this->storedValues)) {
                return;
            }
        }

        // Reset the transport values.
        $transport->setAuthMode($this->storedValues['AuthMode']);
        $transport->setEncryption($this->storedValues['Encryption']);
        $transport->setHost($this->storedValues['Host']);
        $transport->setPassword($this->storedValues['Password']);
        $transport->setPort($this->storedValues['Port']);
        $transport->setUsername($this->storedValues['UserName']);

        // Restarttop (stop and start) the transport so it uses the new values
        // and connects to new server
        $transport->stop();
        $transport->start();
    }
}