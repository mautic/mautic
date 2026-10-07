<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Notification\ContactExportAdminNotification;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ContactExportSchedulerNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationModel $notificationModel,
        private TranslatorInterface $translator,
        private ContactExportAdminNotification $contactExportAdminNotification,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT_SCHEDULED  => 'onContactExportScheduled',
            LeadEvents::POST_CONTACT_EXPORT_SEND_EMAIL => 'onContactExportEmailSent',
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

        $this->contactExportAdminNotification->notifyRequested($event->getContactExportScheduler());
    }

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $this->contactExportAdminNotification->notifyCompleted($event->getContactExportScheduler());
    }
}
