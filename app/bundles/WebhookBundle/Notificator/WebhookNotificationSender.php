<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Notificator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\MissingIdentifierField;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Event\WebhookNotificationEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class WebhookNotificationSender
{
    public function __construct(
        private Environment $twig,
        private NotificationModel $notificationModel,
        private EntityManager $entityManager,
        private MailHelper $mailer,
        private CoreParametersHelper $coreParametersHelper,
        private UserRepository $userRepository,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param array<mixed> $templateParameters
     */
    public function send(Webhook $webhook, string $subject, string $templateName, array $templateParameters): void
    {
        $notificationEvent = $this->dispatcher->dispatch(new WebhookNotificationEvent($webhook));
        if (!$notificationEvent->canSend()) {
            return;
        }
        $users     = $this->getToAndCCUsers($webhook);
        $toUsers   = $users['toUsers'];
        $ccToUser  = $users['ccUser'];

        $details   =  $this->twig->render($templateName, $templateParameters);

        foreach ($toUsers as $user) {
            // Send notification
            $this->notificationModel->addNotification(
                $details,
                'error',
                false,
                $subject,
                null,
                null,
                $user
            );
        }

        $this->sendEmail($toUsers, $ccToUser, $subject, $details);
    }

    /**
     * @return array<string,mixed>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getToAndCCUsers(Webhook $webhook): array
    {
        try {
            $owner = $toUser = $this->entityManager->getReference(User::class, $webhook->getCreatedBy());
        } catch (MissingIdentifierField) {
            $owner = $toUser = null;
        }

        $ccToUser = null;

        if (null !== $webhook->getModifiedBy() && $webhook->getCreatedBy() !== $webhook->getModifiedBy()) {
            $modifiedBy = $this->entityManager->getReference(User::class, $webhook->getModifiedBy());

            $toUser   = $modifiedBy; // Send notification to modifier
            $ccToUser = $owner; // And cc e-mail to owner
        }

        $toUsers = [$toUser];
        if (!$toUser) {
            $toUsers = $this->userRepository->getAllAdminUsers();
        }

        return [
            'toUsers' => $toUsers,
            'ccUser'  => $ccToUser,
        ];
    }

    /**
     * @param array<User|null> $toUsers
     */
    private function sendEmail(array $toUsers, ?User $ccToUser, string $subject, string $details): void
    {
        $emailsArr = [];
        foreach ($toUsers as $user) {
            $emailsArr[] = $user->getEmail();
        }

        $sendToAuthor = $this->coreParametersHelper->get('webhook_send_notification_to_author', 1);
        if ($sendToAuthor) {
            $this->mailer->setTo($emailsArr);
            if ($ccToUser) {
                $this->mailer->setCc([$ccToUser->getEmail()]);
            }
        } else {
            $emailAddresses = array_map('trim', explode(',', $this->coreParametersHelper->get('webhook_notification_email_addresses')));
            $this->mailer->setTo($emailAddresses);
        }

        $this->mailer->setSubject($subject);
        $this->mailer->setBody($details);
        $this->mailer->send(true);
    }

    public function getFromNameForSignature(): string
    {
        return $this->coreParametersHelper->get('mailer_from_name');
    }
}
