<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use SendGrid\Mail;
use SendGrid\Response;

/**
 * Class SendGridWrapper
 * We need to wrap \SendGrid class because of magic methods and testing.
 */
class SendGridWrapper
{
    public function __construct(private \SendGrid $sendGrid)
    {
    }

    /**
     * @return Response
     */
    public function send(Mail $mail)
    {
        return $this->sendGrid->client->mail()->send()->post($mail);
    }
}
