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

/**
 * Class EmailSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

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
            EmailEvents::EMAIL_PARSE        => array('onEmailParse', 0)
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
}