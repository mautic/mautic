<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;
use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadRequestException;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiFacade;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiResponse;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridWrapper;
use SendGrid\Mail;
use SendGrid\Response;

class SendGridApiFacadeTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $sendGridWrapper = $this->getMockBuilder(SendGridWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = $this->getMockBuilder(SendGridApiMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiResponse = $this->getMockBuilder(SendGridApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiFacade = new SendGridApiFacade($sendGridWrapper, $sendGridApiMessage, $sendGridApiResponse);

        $sendGridApiMessage->expects($this->once())
            ->method('getMessage')
            ->with($message)
            ->willReturn($mail);

        $sendGridWrapper->expects($this->once())
            ->method('send')
            ->with($mail)
            ->willReturn($response);

        $sendGridApiResponse->expects($this->once())
            ->method('checkResponse')
            ->with($response);

        $sendGridApiFacade->send($message);
    }

    public function testBadLogin()
    {
        $sendGridWrapper = $this->getMockBuilder(SendGridWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = $this->getMockBuilder(SendGridApiMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiResponse = $this->getMockBuilder(SendGridApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiFacade = new SendGridApiFacade($sendGridWrapper, $sendGridApiMessage, $sendGridApiResponse);

        $sendGridApiMessage->expects($this->once())
            ->method('getMessage')
            ->with($message)
            ->willReturn($mail);

        $sendGridWrapper->expects($this->once())
            ->method('send')
            ->with($mail)
            ->willReturn($response);

        $sendGridApiResponse->expects($this->once())
            ->method('checkResponse')
            ->with($response)
            ->willThrowException(new SendGridBadLoginException('Bad login'));

        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Bad login');

        $sendGridApiFacade->send($message);
    }

    public function testBadRequest()
    {
        $sendGridWrapper = $this->getMockBuilder(SendGridWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = $this->getMockBuilder(SendGridApiMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiResponse = $this->getMockBuilder(SendGridApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiFacade = new SendGridApiFacade($sendGridWrapper, $sendGridApiMessage, $sendGridApiResponse);

        $sendGridApiMessage->expects($this->once())
            ->method('getMessage')
            ->with($message)
            ->willReturn($mail);

        $sendGridWrapper->expects($this->once())
            ->method('send')
            ->with($mail)
            ->willReturn($response);

        $sendGridApiResponse->expects($this->once())
            ->method('checkResponse')
            ->with($response)
            ->willThrowException(new SendGridBadRequestException('Bad request'));

        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Bad request');

        $sendGridApiFacade->send($message);
    }
}
