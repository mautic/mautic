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
    private NotificationModel $notificationModel;
    private TranslatorInterface $translator;

    public function __construct(NotificationModel $notificationModel, TranslatorInterface $translator)
    {
        $this->notificationModel = $notificationModel;
        $this->translator        = $translator;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::POST_CONTACT_EXPORT_SCHEDULED  => 'onContactExportScheduled',
            LeadEvents::POST_CONTACT_EXPORT_SEND_EMAIL => 'onContactExportEmailSent',
        ];
    }

    public function onContactExportScheduled(ContactExportSchedulerEvent $event): void
    {
        $data    = $event->getContactExportScheduler()->getData();
        $message = $this->translator->trans('mautic.lead.export.being.prepared', ['%file_type%' => $data['fileType']]);
        $user    = $event->getContactExportScheduler()->getUser();
        \assert($user instanceof User);

        $this->notificationModel->addNotification(
            $message,
            null,
            false,
            null,
            null,
            null,
            $user
        );
    }

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        $data    = $event->getContactExportScheduler()->getData();
        $message = $this->translator->trans('mautic.lead.export.prepared', ['%file_type%' => $data['fileType']]);
        $user    = $event->getContactExportScheduler()->getUser();
        \assert($user instanceof User);

        $this->notificationModel->addNotification(
            $message,
            null,
            false,
            null,
            null,
            null,
            $user
        );
    }
}
