<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailToUserSubscriber implements EventSubscriberInterface
{
    /** @var SendEmailToUser */
    private $sendEmailToUser;

    public function __construct(SendEmailToUser $sendEmailToUser)
    {
        $this->sendEmailToUser = $sendEmailToUser;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [EmailToUserSubscriber::class => ['onEmailToUser', 0]];
    }

    public function onEmailToUser(TriggerExecutedEvent $event)
    {
        $triggerEvent = $event->getTriggerEvent();
        $config       = $triggerEvent->getProperties();
        $lead         = $event->getLead();

        try {
            $this->sendEmailToUser->sendEmailToUsers($config, $lead);
            $event->setSucceded();
        }
        catch (EmailCouldNotBeSentException $e) {
            $event->setFailed();
        }

        return $event;
    }
}
