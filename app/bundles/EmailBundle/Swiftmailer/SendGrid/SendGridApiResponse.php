<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;
use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadRequestException;
use Monolog\Logger;
use SendGrid\Response;

class SendGridApiResponse
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws SendGridBadLoginException
     * @throws SendGridBadRequestException
     */
    public function checkResponse(Response $response)
    {
        $statusCode = $response->statusCode();

        if ($statusCode >= 200 && $statusCode <= 299) {
            //Request was successful
            return;
        }

        $this->logger->addError(
            'SendGrid response: '.$statusCode,
            ['response' => $response]
        );

        $body    = @json_decode($response->body(), true);
        $message = '';
        if (false !== $body && isset($body['errors'][0]['message'])) {
            $message = $body['errors'][0]['message'];
        }

        if (401 === $statusCode) {
            throw new SendGridBadLoginException($message);
        }

        throw new SendGridBadRequestException($message);
    }
}
