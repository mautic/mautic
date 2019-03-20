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
use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\GetMailMessageEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiFacade;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiResponse;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridWrapper;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use SendGrid\Mail;
use SendGrid\Response;
use Swift_Mime_Message;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GetMailMessageEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that a subscriber with nothing to add will not call any methods
     * on the Mail instance.
     */
    public function testNoMailChanges()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new TestGetMailMessageSubscriber(); // nothing to to
        $dispatcher->addSubscriber($subscriber);

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail->expects($this->never())
            ->method('addCategory');

        $mail->expects($this->never())
            ->method('addCustomArg');

        $apiFacade = $this->createSendGridApiFacade($mail, $message, $dispatcher);

        $apiFacade->send($message);
    }

    /**
     * Tests that multiple subscribers will be able to update the Mail.
     */
    public function testMultipleSubscribers()
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber(new TestGetMailMessageSubscriber(['foo', 'bar']));
        $dispatcher->addSubscriber(new TestGetMailMessageSubscriber([], ['baz' => 'qux']));
        $dispatcher->addSubscriber(new TestGetMailMessageSubscriber(['quux', 'quuz'], ['corge' => 'grault', 'garply' => 'thud']));

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addCategory', 'getCategories', 'addCustomArg', 'getCustomArgs'])
            ->getMock();

        $apiFacade = $this->createSendGridApiFacade($mail, $message, $dispatcher);

        // This is where the action happens
        $apiFacade->send($message);

        // Though the send doesn't return anything, and the $mail instance
        // will only be available to event listeners in real operation, we can
        // access $mail here because it is the same object returned by our
        // mocked ApiMessage instance.
        $categories = $mail->getCategories();
        $customArgs = $mail->getCustomArgs();

        // Test that all categories were added.
        $this->assertContains('foo', $categories);
        $this->assertContains('bar', $categories);
        $this->assertContains('quux', $categories);
        $this->assertContains('quuz', $categories);

        // Test that all custom args were added.
        $this->assertArraySubset(['baz' => 'qux'], $customArgs);
        $this->assertArraySubset(['corge' => 'grault', 'garply' => 'thud'], $customArgs);
    }

    /**
     * Create sendgrid api facade.
     *
     * @param Mail                      $mail
     * @param Swift_Mime_Message        $message
     * @param EventDispatcherInterface  $dispatcher
     *
     * @return SendGridApiFacade
     */
    protected function createSendGridApiFacade(
        Mail $mail,
        Swift_Mime_Message $message,
        EventDispatcherInterface $dispatcher
    ) {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $wrapper = $this->getMockBuilder(SendGridWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $wrapper
            ->method('send')
            ->willReturn($response);

        $apiMessage = $this->getMockBuilder(SendGridApiMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiMessage
            ->method('getMessage')
            ->with($message)
            ->willReturn($mail);

        $apiResponse = $this->getMockBuilder(SendGridApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new SendGridApiFacade($wrapper, $apiMessage, $apiResponse, $dispatcher);
    }

}

/**
 * Test subscriber to add 'categories' and 'custom args' to the event Mail if
 * so instantiated.
 */
class TestGetMailMessageSubscriber implements EventSubscriberInterface
{
    /** @var array */
    protected $categories = [];

    /** @var array */
    protected $customArgs = [];

    /**
     * Get subscribed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SendGridMailEvents::GET_MAIL_MESSAGE => ['onGetMailMessage', 0],
        ];
    }

    /**
     * Constructor.
     *
     * @param array $categories
     */
    public function __construct($categories = [], $customArgs = [])
    {
        $this->categories = $categories;
        $this->customArgs = $customArgs;
    }

    /**
     * @param GetMailMessageEvent $event
     */
    public function onGetMailMessage(GetMailMessageEvent $event)
    {
        $mail = $event->getMail();

        // Add subscriber instance categories to event Mail
        foreach ($this->categories as $category) {
            $mail->addCategory($category);
        }

        // Add subscriber instance custom args to event Mail
        foreach ($this->customArgs as $key => $value) {
            $mail->addCustomArg($key, $value);
        }
    }
}
