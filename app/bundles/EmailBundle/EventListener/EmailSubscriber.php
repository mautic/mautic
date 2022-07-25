<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        IpLookupHelper $ipLookupHelper,
        AuditLogModel $auditLogModel,
        EmailModel $emailModel,
        TranslatorInterface $translator,
        EntityManager $entityManager
    ) {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
        $this->emailModel     = $emailModel;
        $this->translator     = $translator;
        $this->entityManager  = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_POST_SAVE      => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE    => ['onEmailDelete', 0],
            EmailEvents::EMAIL_FAILED         => ['onEmailFailed', 0],
            EmailEvents::EMAIL_RESEND         => ['onEmailResend', 0],
            EmailEvents::ON_TRANSPORT_WEBHOOK => ['onTransportWebhook', -255],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onEmailPostSave(Events\EmailEvent $event)
    {
        $email = $event->getEmail();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'email',
                'object'    => 'email',
                'objectId'  => $email->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onEmailDelete(Events\EmailEvent $event)
    {
        $email = $event->getEmail();
        $log   = [
            'bundle'    => 'email',
            'object'    => 'email',
            'objectId'  => $email->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $email->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Process if an email has failed.
     */
    public function onEmailFailed(Events\QueueEmailEvent $event)
    {
        $message = $event->getMessage();

        if (isset($message->leadIdHash)) {
            $stat = $this->emailModel->getEmailStatus($message->leadIdHash);

            if (null !== $stat) {
                $reason = $this->translator->trans('mautic.email.dnc.failed', [
                    '%subject%' => EmojiHelper::toShort($message->getSubject()),
                ]);
                $this->emailModel->setDoNotContact($stat, $reason);
            }
        }
    }

    /**
     * Process if an email is resent.
     */
    public function onEmailResend(Events\QueueEmailEvent $event)
    {
        $message = $event->getMessage();

        if (isset($message->leadIdHash)) {
            $stat = $this->emailModel->getEmailStatus($message->leadIdHash);
            if (null !== $stat) {
                $stat->upRetryCount();

                $retries = $stat->getRetryCount();
                if ($retries > 3) {
                    //tried too many times so just fail
                    $reason = $this->translator->trans('mautic.email.dnc.retries', [
                        '%subject%' => EmojiHelper::toShort($message->getSubject()),
                    ]);
                    $this->emailModel->setDoNotContact($stat, $reason);
                } else {
                    //set it to try again
                    $event->tryAgain();
                }

                $this->entityManager->persist($stat);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * This is default handling of email transport webhook requests.
     * For custom handling (queues) for specific transport use the same listener with priority higher than -255.
     */
    public function onTransportWebhook(TransportWebhookEvent $event)
    {
        $event->getTransport()->processCallbackRequest($event->getRequest());
    }
}
