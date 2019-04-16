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
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\DataCollectorTranslator;

class WebhookKillNotificator
{
    /**
     * @var DataCollectorTranslator
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
        DataCollectorTranslator $translator,
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
                ['objectAction' => 'view', 'objectId' => $webhook->getId()]
            ).'" data-toggle="ajax">'.$webhook->getName().'</a>';

        $details = $this->translator->trans(
            'mautic.webhook.stopped.details',
            [
                '%reason%'  => $reason,
                '%webhook%' => $htmlUrl,
            ]
        );

        $this->sendMauticNotification($webhook, $subject, $details);
        $this->sendMailNotification($webhook, $subject, $details);
    }

    /**
     * @param string $subject
     * @param string $details
     *
     * @throws ORMException
     */
    private function sendMauticNotification(Webhook $webhook, $subject, $details)
    {
        $owner      = $this->entityManager->getReference('MauticUserBundle:User', $webhook->getCreatedBy());
        /** @var User $user */
        $user = $owner;

        if ($modifiedBy = $webhook->getModifiedBy()) {
            $modifiedBy = $this->entityManager->getReference('MauticUserBundle:User', $modifiedBy);

            if ($modifiedBy !== $owner) {
                $user = $modifiedBy;
            }
        }

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

    /**
     * @param string $subject
     * @param string $details
     *
     * @throws ORMException
     */
    private function sendMailNotification(Webhook $webhook, $subject, $details)
    {
        $mailer = $this->mailer;

        /** @var User $owner */
        $owner = $this->entityManager->getReference('MauticUserBundle:User', $webhook->getCreatedBy());

        $mailer->setTo($owner->getEmail());

        if ($modifiedBy = $webhook->getModifiedBy()) {
            $modifiedBy = $this->entityManager->getReference('MauticUserBundle:User', $webhook->getModifiedBy());

            if ($modifiedBy !== $owner) {
                $mailer->setTo($modifiedBy->getEmail());
                $mailer->setCC($owner);
            }
        }

        $mailer->setSubject($subject);
        $mailer->setBody($details);
        $mailer->send(true);
    }
}
