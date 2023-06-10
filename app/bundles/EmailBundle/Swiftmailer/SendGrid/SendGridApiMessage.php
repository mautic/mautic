<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use SendGrid\Mail;

class SendGridApiMessage
{
    public function __construct(private SendGridMailBase $sendGridMailBase, private SendGridMailPersonalization $sendGridMailPersonalization, private SendGridMailMetadata $sendGridMailMetadata, private SendGridMailAttachment $sendGridMailAttachment)
    {
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

        return $mail;
    }
}
