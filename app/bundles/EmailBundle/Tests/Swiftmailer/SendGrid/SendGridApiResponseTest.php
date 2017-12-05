<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;
use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadRequestException;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiResponse;
use Monolog\Logger;
use SendGrid\Response;

class SendGridApiResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider successfulResponseProvider
     */
    public function testSuccessfulResponse($code)
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = new SendGridApiResponse($logger);

        $response = new Response($code);

        $sendGridApiMessage->checkResponse($response);
    }

    public function successfulResponseProvider()
    {
        return [
            [200],
            [202],
            [250],
            [299],
        ];
    }

    public function testBadLogin()
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = new SendGridApiResponse($logger);

        $response = new Response(401);

        $logger->expects($this->once())
            ->method('addError')
            ->with('SendGrid response: 401', ['response' => $response]);

        $this->expectException(SendGridBadLoginException::class);

        $sendGridApiMessage->checkResponse($response);
    }

    public function testBadRequest()
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = new SendGridApiResponse($logger);

        $body = '{"errors":[{"message":"The attachment content must be base64 encoded.","field":"attachments.0.content","help":"http://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html#message.attachments.content"}]}';

        $response = new Response(410, $body);

        $logger->expects($this->once())
            ->method('addError')
            ->with('SendGrid response: 410', ['response' => $response]);

        $this->expectException(SendGridBadRequestException::class);
        $this->expectExceptionMessage('The attachment content must be base64 encoded.');

        $sendGridApiMessage->checkResponse($response);
    }
}
