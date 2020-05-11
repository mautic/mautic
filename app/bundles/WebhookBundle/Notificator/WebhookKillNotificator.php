<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Notificator;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WebhookKillNotificator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var MailHelper
     */
    private $mailer;

    public function __construct(
        TranslatorInterface $translator,
        Router $router,
        NotificationModel $notificationModel,
        EntityManager $entityManager,
        MailHelper $mailer
    ) {
        $this->translator        = $translator;
        $this->router            = $router;
        $this->notificationModel = $notificationModel;
        $this->entityManager     = $entityManager;
        $this->mailer            = $mailer;
    }

    /**
     * @param string $reason Translatable key
     */
    public function send(Webhook $webhook, $reason)
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
        $owner = $toUser = $this->entityManager->getReference('MauticUserBundle:User', $webhook->getCreatedBy());

        $ccToUser = null;

        if (null !== $webhook->getModifiedBy() && $webhook->getCreatedBy() !== $webhook->getModifiedBy()) {
            $modifiedBy = $this->entityManager->getReference('MauticUserBundle:User', $webhook->getModifiedBy());

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

        $mailer->setTo($toUser->getEmail());

        if ($ccToUser) {
            $mailer->setCc($ccToUser->getEmail());
        }

        $mailer->setSubject($subject);
        $mailer->setBody($details);
        $mailer->send(true);
    }
}
