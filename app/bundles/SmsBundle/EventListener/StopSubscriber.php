<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StopSubscriber implements EventSubscriberInterface
{
    /**
     * @var DoNotContactModel
     */
    private $doNotContactModel;

    /**
     * StopSubscriber constructor.
     */
    public function __construct(DoNotContactModel $doNotContactModel)
    {
        $this->doNotContactModel         = $doNotContactModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SmsEvents::ON_REPLY => ['onReply', 0],
        ];
    }

    public function onReply(ReplyEvent $event)
    {
        $message = $event->getMessage();

        if ('stop' === strtolower($message)) {
            // Unsubscribe the contact
            $this->doNotContactModel->addDncForContact($event->getContact()->getId(), 'sms', DoNotContact::UNSUBSCRIBED);
        }
    }
}
