<?php

namespace MauticPlugin\MauticRecombeeBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;

class WhyMeSubscriber extends CommonSubscriber
{
    /**
     * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents for description.
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            UserEvents::USER_PRE_DELETE  => 'iWarnYou', /* 0 is used as a default priority */
            UserEvents::USER_POST_DELETE => ['whoWillPayForThis', 9999 /* high priority */],
            UserEvents::USER_POST_DELETE => [
                ['youWillPay', 9998 /* a little bit lesser priority */],
                ['userOfYourCreationWill' /* 0 si used as a default priority */],
            ],
        ];
    }

    public function iWarnYou(UserEvent $event)
    {
        trigger_error(
            'I warn you, the skeleton subscriber should be rewritten.'
            .' Besides to your violence to '.$event->getUser()->getName(),
            E_USER_WARNING
        );
    }

    public function whoWillPayForThis()
    {
        throw new \LogicException('Who will pay for forgotten skeleton code?');
    }

    public function youWillPay()
    {
        throw new \LogicException('You will pay for this forgotten skeleton code.');
    }

    public function userOfYourCreationWill()
    {
        throw new \LogicException('And what is worse, user of your creation will pay too for this forgotten skeleton code.');
    }
}
