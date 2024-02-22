<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private const RETRY_COUNT = 3;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private EmailModel $emailModel,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_POST_SAVE      => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE    => ['onEmailDelete', 0],
            EmailEvents::EMAIL_FAILED         => ['onEmailFailed', 0],
            EmailEvents::EMAIL_RESEND         => ['onEmailResend', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onEmailPostSave(Events\EmailEvent $event): void
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
    public function onEmailDelete(Events\EmailEvent $event): void
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
    public function onEmailFailed(Events\QueueEmailEvent $event): void
    {
        $message    = $event->getMessage();
        $leadIdHash = $message->getLeadIdHash();

        if (isset($leadIdHash)) {
            $stat = $this->emailModel->getEmailStatus($leadIdHash);

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
    public function onEmailResend(Events\QueueEmailEvent $event): void
    {
        $message = $event->getMessage();

        if (empty($message->getLeadIdHash())) {
            return;
        }

        $stat = $this->emailModel->getEmailStatus($message->getLeadIdHash());

        if (!$stat) {
            return;
        }

        $stat->upRetryCount();

        if ($stat->getRetryCount() > self::RETRY_COUNT) {
            // tried too many times so just fail
            $reason = $this->translator->trans('mautic.email.dnc.retries', [
                '%subject%' => EmojiHelper::toShort($message->getSubject()),
            ]);
            $this->emailModel->setDoNotContact($stat, $reason);
        } else {
            // set it to try again
            $event->tryAgain();
        }

        $this->emailModel->saveEmailStat($stat);
    }
}
