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

use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailHeader;
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

    /**
     * @var SendGridMailHeader
     */
    private $sendGridMailHeader;

    /**
     * SendGridApiMessage constructor.
     *
     * @param SendGridMailBase            $sendGridMailBase
     * @param SendGridMailPersonalization $sendGridMailPersonalization
     * @param SendGridMailMetadata        $sendGridMailMetadata
     * @param SendGridMailAttachment      $sendGridMailAttachment
     * @param SendGridMailHeader          $sendGridMailHeader
     */
    public function __construct(
        SendGridMailBase $sendGridMailBase,
        SendGridMailPersonalization $sendGridMailPersonalization,
        SendGridMailMetadata $sendGridMailMetadata,
        SendGridMailAttachment $sendGridMailAttachment,
        SendGridMailHeader $sendGridMailHeader
    ) {
        $this->sendGridMailBase            = $sendGridMailBase;
        $this->sendGridMailPersonalization = $sendGridMailPersonalization;
        $this->sendGridMailMetadata        = $sendGridMailMetadata;
        $this->sendGridMailAttachment      = $sendGridMailAttachment;
        $this->sendGridMailHeader          = $sendGridMailHeader;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return Mail
     */
    public function getMessage(\Swift_Mime_Message $message)
    {
        $mail = $this->sendGridMailBase->getSendGridMail($message);

        $this->sendGridMailPersonalization->addPersonalizedDataToMail($mail, $message);
        $this->sendGridMailMetadata->addMetadataToMail($mail, $message);
        $this->sendGridMailAttachment->addAttachmentsToMail($mail, $message);
        $this->sendGridMailHeader->addHeadersToMail($mail, $message);

        return $mail;
    }
}
