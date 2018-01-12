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
use Monolog\Logger;

class SendGridApiFacade
{
    /**
     * @var SendGridWrapper
     */
    private $sendGridWrapper;

    /**
     * @var SendGridApiMessage
     */
    private $sendGridApiMessage;

    /**
     * @var SendGridApiResponse
     */
    private $sendGridApiResponse;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        SendGridWrapper $sendGridWrapper,
        SendGridApiMessage $sendGridApiMessage,
        SendGridApiResponse $sendGridApiResponse,
        Logger $logger
    ) {
        $this->sendGridWrapper     = $sendGridWrapper;
        $this->sendGridApiMessage  = $sendGridApiMessage;
        $this->sendGridApiResponse = $sendGridApiResponse;
        $this->logger              = $logger;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_Message $message)
    {
        $mail = $this->sendGridApiMessage->getMessage($message);
        $this->logger->debug('SendGridApi - Swift_Mime_Message:', [$message]);
        $this->logger->debug('SendGridApi - Mail:', [$mail]);
        $response = $this->sendGridWrapper->send($mail);

        try {
            $this->sendGridApiResponse->checkResponse($response);
        } catch (SendGridBadLoginException $e) {
            throw new \Swift_TransportException($e->getMessage());
        } catch (SendGridBadRequestException $e) {
            throw new \Swift_TransportException($e->getMessage());
        }
    }
}
