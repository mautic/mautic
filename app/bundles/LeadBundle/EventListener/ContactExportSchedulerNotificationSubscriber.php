<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
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
        private MailHelper $mailHelper,
        private CoreParametersHelper $coreParametersHelper,
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

        if (!$this->isAdminContactExportNotificationEnabled()) {
            return;
        }

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

    public function onContactExportEmailSent(ContactExportSchedulerEvent $event): void
    {
        if (!$this->isAdminContactExportNotificationEnabled()) {
            return;
        }

        /** @var User $requestingUser */
        $requestingUser  = $event->getContactExportScheduler()->getUser();
        $requestedAt     = $event->getContactExportScheduler()->getScheduledDateTime();
        $completedAt     = new \DateTimeImmutable();
        $fileType        = strtoupper((string) ($event->getContactExportScheduler()->getData()['fileType'] ?? ''));
        $adminUsers      = $this->getAdminUsersToNotify($requestingUser);

        if ([] === $adminUsers) {
            return;
        }

        $message = $this->translator->trans(
            'mautic.lead.export.admin.email',
            [
                '%requesting_user_name%'  => $requestingUser->getName(),
                '%requesting_user_email%' => $requestingUser->getEmail(),
                '%requested_at%'          => $requestedAt->format('Y-m-d H:i:s P'),
                '%completed_at%'          => $completedAt->format('Y-m-d H:i:s P'),
                '%status%'                => 'Completed',
                '%file_type%'             => $fileType,
            ]
        );

        $primaryAdmin = array_shift($adminUsers);
        \assert($primaryAdmin instanceof User);

        $mailer = $this->mailHelper->getMailer(true);
        $mailer->setTo([$primaryAdmin->getEmail() => $primaryAdmin->getName()]);

        if ([] !== $adminUsers) {
            $mailer->setCc(
                array_reduce(
                    $adminUsers,
                    static function (array $recipients, User $adminUser): array {
                        $recipients[$adminUser->getEmail()] = $adminUser->getName();

                        return $recipients;
                    },
                    []
                )
            );
        }

        $mailer->setSubject($this->translator->trans('mautic.lead.export.admin.email_subject'));
        $mailer->setBody($message);
        $mailer->parsePlainText($message);
        $mailer->send(true);
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

    private function isAdminContactExportNotificationEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('contact_export_notify_admins');
    }
}
