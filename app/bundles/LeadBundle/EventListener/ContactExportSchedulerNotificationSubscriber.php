<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Event\ContactExportEmailSentEvent;
use Mautic\LeadBundle\Event\ContactExportScheduledEvent;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\Notification\ContactExportAdminNotification;
use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ContactExportSchedulerNotificationSubscriber
{
    public function __construct(
        private NotificationModel $notificationModel,
        private TranslatorInterface $translator,
        private ContactExportAdminNotification $contactExportAdminNotification,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ContactExportScheduledEvent::class)]
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

        $this->contactExportAdminNotification->notifyRequested($event->getContactExportScheduler());
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ContactExportEmailSentEvent::class)]
    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $this->contactExportAdminNotification->notifyCompleted($event->getContactExportScheduler());
    }
}
