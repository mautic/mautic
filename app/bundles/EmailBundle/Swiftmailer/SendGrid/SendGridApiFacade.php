<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;
use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadRequestException;
use Mautic\EmailBundle\Swiftmailer\SwiftmailerFacadeInterface;

class SendGridApiFacade implements SwiftmailerFacadeInterface
{
    public function __construct(private SendGridWrapper $sendGridWrapper, private SendGridApiMessage $sendGridApiMessage, private SendGridApiResponse $sendGridApiResponse)
    {
    }

    /**
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_SimpleMessage $message)
    {
        $mail = $this->sendGridApiMessage->getMessage($message);

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
