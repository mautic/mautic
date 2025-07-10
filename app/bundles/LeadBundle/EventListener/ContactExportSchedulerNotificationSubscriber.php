<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactExportSchedulerNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationModel $notificationModel,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT_SCHEDULED  => 'onContactExportScheduled',
        ];
    }

    public function onContactExportScheduled(ContactExportSchedulerEvent $event): void
    {
        /** @var User $user */
        $user    = $event->getContactExportScheduler()->getUser();
        $message = $this->translator->trans('mautic.lead.export.being.prepared', ['%user_email%' => $user->getEmail()]);

        $this->notificationModel->addNotification(
            $message,
            'info',
            false,
            'mautic.lead.export.being.prepared.header',
            null,
            null,
            $user
        );
    }
}
