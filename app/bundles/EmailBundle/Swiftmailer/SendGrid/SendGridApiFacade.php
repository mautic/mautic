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

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;

class SendGridApiFacade
{
    /**
     * @var \SendGrid
     */
    private $sendGrid;

    /**
     * @var SendGridApiMessage
     */
    private $sendGridApiMessage;

    /**
     * @var SendGridApiResponse
     */
    private $sendGridApiResponse;

    public function __construct(\SendGrid $sendGrid, SendGridApiMessage $sendGridApiMessage, SendGridApiResponse $sendGridApiResponse)
    {
        $this->sendGrid            = $sendGrid;
        $this->sendGridApiMessage  = $sendGridApiMessage;
        $this->sendGridApiResponse = $sendGridApiResponse;
    }

    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $mail = $this->sendGridApiMessage->getMessage($message);

        $response = $this->sendGrid->client->mail()->send()->post($mail);

        try {
            $this->sendGridApiResponse->checkResponse($response);
        } catch (SendGridBadLoginException $e) {
            throw new \Swift_TransportException();
        }
    }
}
