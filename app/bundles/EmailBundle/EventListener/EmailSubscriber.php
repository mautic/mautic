<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreDelete;
use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Event\EmailDisplayEvent;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Model\EmailDraftModel;
use Mautic\EmailBundle\Model\EmailModel;
use ReflectionObject;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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

    #[AsEventListener]
    public function cloneParentEmailDataForVariant(EntityPreSave $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        if ($email->isVariant()) {
            $this->emailRepository->cloneFromParentToVariant($email);
        }
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener]
    public function onEmailPostSave(EntityPostSave $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

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
    #[AsEventListener(
        event: EmailSendEvent::class,
        priority: 200
    )]
    #[AsEventListener(
        event: EmailDisplayEvent::class,
        priority: 200
    )]
    public function onEmailSendAddPreheaderText(EmailSendEvent $event): void
    {
        $email = $event->getEmail();
        $html  = $event->getContent();

        if ($email instanceof Email && $email->getPreheaderText()) {
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
    #[AsEventListener]
    public function onEmailDelete(EntityPostDelete $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        $log = [
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
    #[AsEventListener(
        event: EmailEvents::EMAIL_FAILED,
    )]
    public function onEmailFailed(Events\QueueEmailEvent $event): void
    {
        $message    = $event->getMessage();
        $leadIdHash = $message->getLeadIdHash();

        if (isset($leadIdHash)) {
            $stat = $this->emailModel->getEmailStatus($leadIdHash);

            if ($stat instanceof Stat) {
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
    #[AsEventListener(
        event: EmailEvents::EMAIL_RESEND,
    )]
    public function onEmailResend(Events\QueueEmailEvent $event): void
    {
        $message = $event->getMessage();

        if (empty($message->getLeadIdHash())) {
            return;
        }

        $stat = $this->emailModel->getEmailStatus($message->getLeadIdHash());

        if (!$stat instanceof Stat) {
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

    #[AsEventListener(event: EmailEditSubmitEvent::class)]
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

    #[AsEventListener]
    public function deleteEmailDraft(EntityPreDelete $event): void
    {
        $email = $event->getEntity();
        if (!$email instanceof Email) {
            return;
        }

        try {
            $this->emailDraftModel->deleteDraft($email);
        } catch (NotFoundHttpException) {
            // No associated draft found for deletion. We have nothing to do here. Return.
            return;
        }
    }

    private function revertEmailModifications(Email $liveEmail, Email $editedEmail): void
    {
        $liveEmailReflection   = new ReflectionObject($liveEmail);
        $editedEmailReflection = new ReflectionObject($editedEmail);
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
