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
use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadRequestException;

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

    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_Message $message)
    {
        $mail = $this->sendGridApiMessage->getMessage($message);

        $response = $this->sendGrid->client->mail()->send()->post($mail);

        try {
            $this->sendGridApiResponse->checkResponse($response);
        } catch (SendGridBadLoginException $e) {
            throw new \Swift_TransportException($e->getMessage());
        } catch (SendGridBadRequestException $e) {
            throw new \Swift_TransportException($e->getMessage());
        }
    }
}
