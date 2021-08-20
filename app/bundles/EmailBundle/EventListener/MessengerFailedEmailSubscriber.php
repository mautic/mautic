<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Mautic\EmailBundle\Messenger\EmailMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class MessengerFailedEmailSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;

    private CoreParametersHelper $coreParametersHelper;

    public function __construct(EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $this->dispatcher           = $dispatcher;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $envelope = $event->getEnvelope();
        $message  = $envelope->getMessage();
        if ($event->getReceiverName() === $this->coreParametersHelper->get('messenger_transport_email_receiver') && $message instanceof EmailMessage && $this->dispatcher->hasListeners(EmailEvents::EMAIL_FAILED)) {
            $event = new QueueEmailEvent($message->getMauticMessage());
            $this->dispatcher->dispatch(EmailEvents::EMAIL_FAILED, $event);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -90],
        ];
    }
}
