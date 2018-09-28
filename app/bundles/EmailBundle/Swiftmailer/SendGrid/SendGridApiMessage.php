<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\GetMailMessageEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use SendGrid\Mail;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendGridApiMessage
{
    /**
     * @var SendGridMailBase
     */
    private $sendGridMailBase;

    /**
     * @var SendGridMailPersonalization
     */
    private $sendGridMailPersonalization;

    /**
     * @var SendGridMailMetadata
     */
    private $sendGridMailMetadata;

    /**
     * @var SendGridMailAttachment
     */
    private $sendGridMailAttachment;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        SendGridMailBase $sendGridMailBase,
        SendGridMailPersonalization $sendGridMailPersonalization,
        SendGridMailMetadata $sendGridMailMetadata,
        SendGridMailAttachment $sendGridMailAttachment,
        EventDispatcherInterface $dispatcher
    ) {
        $this->sendGridMailBase            = $sendGridMailBase;
        $this->sendGridMailPersonalization = $sendGridMailPersonalization;
        $this->sendGridMailMetadata        = $sendGridMailMetadata;
        $this->sendGridMailAttachment      = $sendGridMailAttachment;
        $this->dispatcher                  = $dispatcher;
    }

    /**
     * @return Mail
     */
    public function getMessage(\Swift_Mime_SimpleMessage $message)
    {
        $mail = $this->sendGridMailBase->getSendGridMail($message);

        $this->sendGridMailPersonalization->addPersonalizedDataToMail($mail, $message);
        $this->sendGridMailMetadata->addMetadataToMail($mail, $message);
        $this->sendGridMailAttachment->addAttachmentsToMail($mail, $message);

        $this->dispatchGetMailMessageEvent($mail, $message);

        return $mail;
    }

    /**
     * Dispatch GET_MAIL_MESSAGE event.
     *
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    private function dispatchGetMailMessageEvent(Mail $mail, \Swift_Mime_Message $message)
    {
        $event = new GetMailMessageEvent($mail, $message);

        $this->dispatcher->dispatch(SendGridMailEvents::GET_MAIL_MESSAGE, $event);
    }
}
