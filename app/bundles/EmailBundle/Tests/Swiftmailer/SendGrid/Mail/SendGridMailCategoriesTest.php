<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\SendGridMailCategoriesEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailCategories;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use SendGrid\Mail;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendGridMailCategoriesTest extends \PHPUnit_Framework_TestCase
{

    public function testNotMauticMessage()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new TestCategoriesSubscriber();
        $dispatcher->addSubscriber($subscriber);

        $sendGridMailCategories = new SendGridMailCategories($dispatcher);

        $message = $this->getMockBuilder(\Swift_Mime_Message::class)
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail->expects($this->never())
            ->method('addCategory');

        $sendGridMailCategories->addCategoriesToMail($mail, $message);
    }

    public function testNoCategories()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = new TestCategoriesSubscriber(); // no args = no category
        $dispatcher->addSubscriber($subscriber);

        $sendGridMailCategories = new SendGridMailCategories($dispatcher);

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail->expects($this->never())
            ->method('addCategory');

        $sendGridMailCategories->addCategoriesToMail($mail, $message);
    }

    public function testCategories()
    {
        $dispatcher  = new EventDispatcher();
        $subscriber1 = new TestCategoriesSubscriber('foo');
        $subscriber2 = new TestCategoriesSubscriber('bar');
        $dispatcher->addSubscriber($subscriber1);
        $dispatcher->addSubscriber($subscriber2);

        $sendGridMailCategories = new SendGridMailCategories($dispatcher);

        $message = $this->getMockBuilder(MauticMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addCategory', 'getCategories'])
            ->getMock();

        $sendGridMailCategories->addCategoriesToMail($mail, $message);

        $categories = $mail->getCategories();

        $this->assertContains('foo', $categories);
        $this->assertContains('bar', $categories);
    }
}

/**
 * Test subscriber to add a Category to the event if so instantiated.
 */
class TestCategoriesSubscriber implements EventSubscriberInterface
{
    /** @var string|null */
    protected $category = null;

    /**
     * Get subscribed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SendGridMailEvents::ADD_CATEGORIES => ['onAddCategories', 0],
        ];
    }

    /**
     * Constructor.
     *
     * @param string|null $category
     */
    public function __construct($category = null)
    {
        $this->category = $category;
    }

    /*
     * @param SendGridMailCategoriesEvent $event
     */
    public function onAddCategories(SendGridMailCategoriesEvent $event)
    {
        if ($this->category) {
            $event->addCategory($this->category);
        }
    }
}
