<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\SendGrid\Event\SendGridMailCategoriesEvent;
use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridMailEvents;
use SendGrid\Mail;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendGridMailCategories
{
    /** @var EventDispatcher */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addCategoriesToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        if (!$message instanceof MauticMessage) {
            return;
        }

        $event = new SendGridMailCategoriesEvent($mail, $message);

        $this->dispatcher->dispatch(SendGridMailEvents::ADD_CATEGORIES, $event);

        foreach ($event->getCategories() as $category) {
            $mail->addCategory($category);
        }
    }
}
