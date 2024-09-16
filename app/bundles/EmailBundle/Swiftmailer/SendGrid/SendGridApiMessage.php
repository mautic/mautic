<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use SendGrid\Mail;

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

    public function __construct(
        SendGridMailBase $sendGridMailBase,
        SendGridMailPersonalization $sendGridMailPersonalization,
        SendGridMailMetadata $sendGridMailMetadata,
        SendGridMailAttachment $sendGridMailAttachment
    ) {
        $this->sendGridMailBase            = $sendGridMailBase;
        $this->sendGridMailPersonalization = $sendGridMailPersonalization;
        $this->sendGridMailMetadata        = $sendGridMailMetadata;
        $this->sendGridMailAttachment      = $sendGridMailAttachment;
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
