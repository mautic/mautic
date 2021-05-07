<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Event;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\MailSendResponseEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiFacade;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiResponse;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridWrapper;
use Monolog\Logger;
use SendGrid\Mail;
use SendGrid\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailSendResponseEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests that a subscriber to the SendGridMailEvents::MAIL_SEND_RESPONSE
     * event will be called when the facade gets a valid response and that the
     * response is made available to the subscriber.
     */
    public function testValidMailSendResponse()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new TestMailSendResponseSubscriber();
        $dispatcher->addSubscriber($subscriber);

        $response = new Response(200, 'Good Request');

        $this->createFacadeAndSendMessage($dispatcher, $response);

        $this->assertTrue($subscriber->wasCalled());
        $this->assertSame($subscriber->getResponse(), $response);
        $this->assertEquals($subscriber->getResponse()->statusCode(), 200);
        $this->assertEquals($subscriber->getResponse()->body(), 'Good Request');
    }

    /**
     * Tests that a subscriber to the SendGridMailEvents::MAIL_SEND_RESPONSE
     * event will not be called when the facade gets a failed response.
     */
    public function testFailedMailSendResponse()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new TestMailSendResponseSubscriber();
        $dispatcher->addSubscriber($subscriber);

        $response = new Response(400, json_encode(['errors' => [['message' => 'Bad Request']]]));

        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->createFacadeAndSendMessage($dispatcher, $response);

        $this->assertFalse($subscriber->wasCalled());
    }

    /**
     * Create SendGridApiFacade and send message with specified success.
     *
     * @param bool $success Whether response is successful
     */
    protected function createFacadeAndSendMessage(
        EventDispatcherInterface $dispatcher,
        Response $response
    ) {
        $wrapper = $this->getMockBuilder(SendGridWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $wrapper
            ->method('send')
            ->willReturn($response);

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiMessage = $this->getMockBuilder(SendGridApiMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiMessage
            ->method('getMessage')
            ->with($message)
            ->willReturn($mail);

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiResponse = new SendGridApiResponse($logger);

        $apiFacade = new SendGridApiFacade(
            $wrapper,
            $apiMessage,
            $apiResponse,
            $dispatcher
        );

        $apiFacade->send($message);
    }
}

/**
 * Test subscriber records whether it was called.
 */
class TestMailSendResponseSubscriber implements EventSubscriberInterface
{
    /** @var bool */
    protected $called = false;

    /** @var Response */
    protected $response;

    /**
     * Get subscribed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SendGridMailEvents::MAIL_SEND_RESPONSE => ['onSendMailResponse', 0],
        ];
    }

    public function onSendMailResponse(MailSendResponseEvent $event)
    {
        $this->called   = true;
        $this->response = $event->getResponse();
    }

    /**
     * Was called.
     *
     * @return bool
     */
    public function wasCalled()
    {
        return $this->called;
    }

    /**
     * Get response.
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
