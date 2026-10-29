<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Event\EmailDisplayEvent;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailPostDeleteEvent;
use Mautic\EmailBundle\Event\EmailPostSaveEvent;
use Mautic\EmailBundle\Event\EmailPreDeleteEvent;
use Mautic\EmailBundle\Event\EmailPreSaveEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Model\EmailDraftModel;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailSubscriber
{
    public const string PREHEADER_HTML_ELEMENT_BEFORE  = '<div class="preheader" style="font-size:1px;line-height:1px;display:none;color:#fff;max-height:0;max-width:0;opacity:0;overflow:hidden">';

    public const string PREHEADER_HTML_ELEMENT_AFTER   = '</div>';

    public const string PREHEADER_HTML_SEARCH_PATTERN  = '/<body[^>]*>.*?<div class="preheader"[^>]*>(.*?)<\/div>/s';

    public const string PREHEADER_HTML_REPLACE_PATTERN = '/<div class="preheader"[^>]*>(.*?)<\/div>/s';

    private const int RETRY_COUNT = 3;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private EmailModel $emailModel,
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager,
        private EmailDraftModel $emailDraftModel,
        private EmailRepository $emailRepository,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailPreSaveEvent::class)]
    public function cloneParentEmailDataForVariant(EmailEvent $event): void
    {
        $email = $event->getEmail();
        if ($email->isVariant()) {
            $this->emailRepository->cloneFromParentToVariant($email);
        }
    }

    /**
     * Add an entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailPostSaveEvent::class)]
    public function onEmailPostSave(EmailEvent $event): void
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
     * Add preheader text to email body.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailSendEvent::class, priority: 200)]
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailDisplayEvent::class, priority: 200)]
    public function onEmailSendAddPreheaderText(EmailSendEvent $event): void
    {
        $email = $event->getEmail();
        $html  = $event->getContent();

        if ($email && $email->getPreheaderText()) {
            $preheaderTextElement = self::PREHEADER_HTML_ELEMENT_BEFORE.$email->getPreheaderText().self::PREHEADER_HTML_ELEMENT_AFTER;
            $preheaderExists      = preg_match(self::PREHEADER_HTML_SEARCH_PATTERN, $html, $preheaderMatches);
            if ($preheaderExists) {
                $html = preg_replace(self::PREHEADER_HTML_REPLACE_PATTERN, $preheaderTextElement, $html);
            } elseif (preg_match('/(<body[^\>]*>)/i', $html, $contentMatches)) {
                $html = str_ireplace($contentMatches[0], $contentMatches[0]."\n".$preheaderTextElement, $html);
            }
            $event->setContent($html);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailPostDeleteEvent::class)]
    public function onEmailDelete(EmailEvent $event): void
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailEvents::EMAIL_FAILED)]
    public function onEmailFailed(Events\QueueEmailEvent $event): void
    {
        $message    = $event->getMessage();
        $leadIdHash = $message->getLeadIdHash();

        if (isset($leadIdHash)) {
            $stat = $this->emailModel->getEmailStatus($leadIdHash);

            if (null !== $stat) {
                $reason = $this->translator->trans('mautic.email.dnc.failed', [
                    '%subject%' => $message->getSubject(),
                ]);
                $this->emailModel->setDoNotContact($stat, $reason);
            }
        }
    }

    /**
     * Process if an email is resent.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailEvents::EMAIL_RESEND)]
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
                '%subject%' => $message->getSubject(),
            ]);
            $this->emailModel->setDoNotContact($stat, $reason);
        } else {
            // set it to try again
            $event->tryAgain();
        }

        $this->emailModel->saveEmailStat($stat);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailEditSubmitEvent::class)]
    public function manageEmailDraft(EmailEditSubmitEvent $event): void
    {
        $liveEmail   = $event->getPreviousEmail();
        $editedEmail = $event->getCurrentEmail();

        if (
            ($event->isSaveAndClose() || $event->isApply())
            && $editedEmail->hasDraft()
        ) {
            $emailDraft = $editedEmail->getDraft();
            $emailDraft->setHtml($editedEmail->getCustomHtml());
            $emailDraft->setTemplate($editedEmail->getTemplate());
            $editedEmail->setCustomHtml($liveEmail->getCustomHtml());
            $editedEmail->setTemplate($liveEmail->getTemplate());
            $this->entityManager->persist($emailDraft);
            $this->entityManager->persist($editedEmail);
        }

        if ($event->isSaveAsDraft()) {
            $emailDraft = $this
                ->emailDraftModel
                ->createDraft($editedEmail, $editedEmail->getCustomHtml(), $editedEmail->getTemplate());

            $editedEmail->setCustomHtml($liveEmail->getCustomHtml());
            $editedEmail->setTemplate($liveEmail->getTemplate());
            $editedEmail->setDraft($emailDraft);
            $this->emailModel->saveEntity($editedEmail);
        }

        if ($event->isDiscardDraft()) {
            $this->revertEmailModifications($liveEmail, $editedEmail);
            $this->emailDraftModel->deleteDraft($editedEmail);
            $editedEmail->setDraft(null);
            $this->entityManager->persist($editedEmail);
        }

        if ($event->isApplyDraft()) {
            $this->emailDraftModel->deleteDraft($editedEmail);
            $editedEmail->setDraft(null);
        }

        $this->entityManager->flush();
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailPreDeleteEvent::class)]
    public function deleteEmailDraft(EmailEvent $event): void
    {
        try {
            $this->emailDraftModel->deleteDraft($event->getEmail());
        } catch (NotFoundHttpException) {
            // No associated draft found for deletion. We have nothing to do here. Return.
            return;
        }
    }

    private function revertEmailModifications(Email $liveEmail, Email $editedEmail): void
    {
        $liveEmailReflection   = new \ReflectionObject($liveEmail);
        $editedEmailReflection = new \ReflectionObject($editedEmail);
        foreach ($liveEmailReflection->getProperties() as $property) {
            if (in_array($property->getName(), ['id', 'emailType'])) {
                continue;
            }

            $name                = $property->getName();
            $value               = $property->getValue($liveEmail);
            $editedEmailProperty = $editedEmailReflection->getProperty($name);
            $editedEmailProperty->setValue($editedEmail, $value);
        }
    }
}
