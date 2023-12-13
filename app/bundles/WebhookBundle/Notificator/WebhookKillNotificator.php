<?php

namespace Mautic\WebhookBundle\Notificator;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebhookKillNotificator
{
    public function __construct(
        private TranslatorInterface $translator,
        private Router $router,
        private NotificationModel $notificationModel,
        private EntityManager $entityManager,
        private MailHelper $mailer,
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    /**
     * @param string $reason Translatable key
     */
    public function send(Webhook $webhook, $reason): void
    {
        $subject = $this->translator->trans('mautic.webhook.stopped');
        $reason  = $this->translator->trans($reason);
        $htmlUrl = '<a href="'.$this->router->generate(
            'mautic_webhook_action',
            ['objectAction' => 'view', 'objectId' => $webhook->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        ).'" data-toggle="ajax">'.$webhook->getName().'</a>';

        $details = $this->translator->trans(
            'mautic.webhook.stopped.details',
            [
                '%reason%'  => $reason,
                '%webhook%' => $htmlUrl,
            ]
        );

        /** @var User $owner */
        $owner = $toUser = $this->entityManager->getReference(\Mautic\UserBundle\Entity\User::class, $webhook->getCreatedBy());

        $ccToUser = null;

        if (null !== $webhook->getModifiedBy() && $webhook->getCreatedBy() !== $webhook->getModifiedBy()) {
            $modifiedBy = $this->entityManager->getReference(\Mautic\UserBundle\Entity\User::class, $webhook->getModifiedBy());

            $toUser   = $modifiedBy; // Send notification to modifier
            $ccToUser = $owner; // And cc e-mail to owner
        }

        // Send notification
        $this->notificationModel->addNotification(
            $details,
            'error',
            false,
            $subject,
            null,
            null,
            $toUser
        );

        // Send e-mail
        $mailer = $this->mailer;

        $sendToAuthor = $this->coreParametersHelper->get('webhook_send_notification_to_author', 1);
        if ($sendToAuthor) {
            $mailer->setTo($toUser->getEmail());
            if ($ccToUser) {
                $mailer->setCc($ccToUser->getEmail());
            }
        } else {
            $emailAddresses = array_map('trim', explode(',', $this->coreParametersHelper->get('webhook_notification_email_addresses')));
            $mailer->setTo($emailAddresses);
        }

        $mailer->setSubject($subject);
        $mailer->setBody($details);
        $mailer->send(true);
    }
}
