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
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use SendGrid\Mail;
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

        $apiMessage = $this->createSendgridApiMessage($dispatcher, $mail, $message);

        $apiMail = $apiMessage->getMessage($message);
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

        $apiMessage = $this->createSendgridApiMessage($dispatcher, $mail, $message);
        $apiMail    = $apiMessage->getMessage($message);

        $categories = $apiMail->getCategories();
        $customArgs = $apiMail->getCustomArgs();

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
     * Create sendgrid api message.
     *
     * @param EventDispatcherInterface $dispatcher
     *
     * @return SendGridApiMessage
     */
    protected function createSendgridApiMessage(EventDispatcherInterface $dispatcher, Mail $mail, \Swift_Mime_Message $message)
    {
        $sendGridMailBase = $this->getMockBuilder(SendGridMailBase::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailPersonalization = $this->getMockBuilder(SendGridMailPersonalization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailMetadata = $this->getMockBuilder(SendGridMailMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridMailAttachment = $this->getMockBuilder(SendGridMailAttachment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sendGridApiMessage = new SendGridApiMessage(
            $sendGridMailBase,
            $sendGridMailPersonalization,
            $sendGridMailMetadata,
            $sendGridMailAttachment,
            $dispatcher
        );

        $sendGridMailBase->expects($this->once())
            ->method('getSendGridMail')
            ->with($message)
            ->willReturn($mail);

        return $sendGridApiMessage;
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
