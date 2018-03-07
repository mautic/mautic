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
     * @param Response $response
     *
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
        if ($body !== false && isset($body['errors'][0]['message'])) {
            $message = $body['errors'][0]['message'];
        }

        if ($statusCode === 401) {
            throw new SendGridBadLoginException($message);
        }

        throw new SendGridBadRequestException($message);
    }
}
