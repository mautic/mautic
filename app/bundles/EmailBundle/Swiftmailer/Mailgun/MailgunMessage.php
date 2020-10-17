<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Mailgun;

use Mautic\EmailBundle\Swiftmailer\Mailgun\Mail\MailgunMailAttachment;
use Mautic\EmailBundle\Swiftmailer\Mailgun\Mail\MailgunMailBase;
use Mautic\EmailBundle\Swiftmailer\Mailgun\Mail\MailgunMailMetadata;
use Mautic\EmailBundle\Swiftmailer\Mailgun\Mail\MailgunMailPersonalization;

class MailgunMessage
{
    /**
     * @var MailgunMailBase
     */
    private $mailgunMailBase;

    /**
     * @var MailgunMailPersonalization
     */
    private $mailgunMailPersonalization;

    /**
     * @var MailgunMailMetadata
     */
    private $mailgundMailMetadata;

    /**
     * @var MailgunMailAttachment
     */
    private $mailgunMailAttachment;

    public function __construct(
        MailgunMailBase $mailgunMailBase,
        MailgunMailPersonalization $mailgunMailPersonalization,
        MailgunMailMetadata $mailgundMailMetadata,
        MailgunMailAttachment $mailgunMailAttachment
    ) {
        $this->mailgunMailBase             = $mailgunMailBase;
        $this->mailgunMailPersonalization  = $mailgunMailPersonalization;
        $this->mailgundMailMetadata        = $mailgundMailMetadata;
        $this->mailgunMailAttachment       = $mailgunMailAttachment;
    }

    /**
     * @return Mail
     */
    public function getMessage(\Swift_Mime_SimpleMessage $message)
    {
        $mail = $this->mailgunMailBase->getMailgundMail($message);

        $this->mailgunMailPersonalization->addPersonalizedDataToMail($mail, $message);
        $this->mailgundMailMetadata->addMetadataToMail($mail, $message);
        $this->mailgunMailAttachment->addAttachmentsToMail($mail, $message);

        return $mail;
    }
}
