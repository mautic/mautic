<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactExportSchedulerNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationModel $notificationModel,
        private TranslatorInterface $translator,
        private UserRepository $userRepository,
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

        foreach ($this->getAdminUsersToNotify($user) as $adminUser) {
            $requestedAt = $event->getContactExportScheduler()->getScheduledDateTime();

            $this->notificationModel->addNotification(
                $this->translator->trans(
                    'mautic.lead.export.admin.notification',
                    [
                        '%requesting_user_name%'  => $user->getName(),
                        '%requesting_user_email%' => $user->getEmail(),
                        '%requested_at%'          => $requestedAt->format('Y-m-d H:i:s P'),
                        '%file_type%'             => strtoupper((string) ($event->getContactExportScheduler()->getData()['fileType'] ?? '')),
                    ]
                ),
                'info',
                false,
                'mautic.lead.export.admin.notification.header',
                null,
                \DateTime::createFromImmutable($requestedAt),
                $adminUser
            );
        }
    }

    /**
     * @return User[]
     */
    private function getAdminUsersToNotify(User $requestingUser): array
    {
        return array_values(
            array_filter(
                $this->userRepository->getAllAdminUsers(),
                static fn (User $adminUser): bool => $adminUser->isAdmin()
                    && $adminUser->isPublished()
                    && $adminUser->getId() !== $requestingUser->getId()
            )
        );
    }
}
