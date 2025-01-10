<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event as Events;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Model\EmailDraftModel;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    public const PREHEADER_HTML_ELEMENT_BEFORE  = '<div class="preheader" style="font-size:1px;line-height:1px;display:none;color:#fff;max-height:0;max-width:0;opacity:0;overflow:hidden">';
    public const PREHEADER_HTML_ELEMENT_AFTER   = '</div>';
    public const PREHEADER_HTML_SEARCH_PATTERN  = '/<body[^>]*>.*?<div class="preheader"[^>]*>(.*?)<\/div>/s';
    public const PREHEADER_HTML_REPLACE_PATTERN = '/<div class="preheader"[^>]*>(.*?)<\/div>/s';

    private const RETRY_COUNT = 3;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private EmailModel $emailModel,
        private TranslatorInterface $translator,
        private EntityManager $entityManager,
        private EmailDraftModel $emailDraftModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_POST_SAVE      => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_ON_SEND        => ['onEmailSendAddPreheaderText', 200],
            EmailEvents::EMAIL_ON_DISPLAY     => ['onEmailSendAddPreheaderText', 200],
            EmailEvents::EMAIL_POST_DELETE    => ['onEmailDelete', 0],
            EmailEvents::EMAIL_FAILED         => ['onEmailFailed', 0],
            EmailEvents::EMAIL_RESEND         => ['onEmailResend', 0],
            EmailEvents::ON_EMAIL_EDIT_SUBMIT => ['manageEmailDraft'],
            EmailEvents::EMAIL_PRE_DELETE     => ['deleteEmailDraft'],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
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
    public function onEmailSendAddPreheaderText(Events\EmailSendEvent $event): void
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

    public function manageEmailDraft(EmailEditSubmitEvent $event): void
    {
        $liveEmail   = $event->getPreviousEmail();
        $editedEmail = $event->getCurrentEmail();

        if (
            ((true === $event->isSaveAndClose()) || (true === $event->isApply()))
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

        if (true === $event->isSaveAsDraft()) {
            $emailDraft = $this
                ->emailDraftModel
                ->createDraft($editedEmail, $editedEmail->getCustomHtml(), $editedEmail->getTemplate());

            $editedEmail->setCustomHtml($liveEmail->getCustomHtml());
            $editedEmail->setTemplate($liveEmail->getTemplate());
            $editedEmail->setDraft($emailDraft);
            $this->emailModel->saveEntity($editedEmail);
        }

        if (true === $event->isDiscardDraft()) {
            $this->revertEmailModifications($liveEmail, $editedEmail);
            $this->emailDraftModel->deleteDraft($editedEmail);
            $editedEmail->setDraft(null);
            $this->entityManager->persist($editedEmail);
        }

        if (true === $event->isApplyDraft()) {
            $this->emailDraftModel->deleteDraft($editedEmail);
            $editedEmail->setDraft(null);
        }

        $this->entityManager->flush();
    }

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

            $property->setAccessible(true);
            $name                = $property->getName();
            $value               = $property->getValue($liveEmail);
            $editedEmailProperty = $editedEmailReflection->getProperty($name);
            $editedEmailProperty->setAccessible(true);
            $editedEmailProperty->setValue($editedEmail, $value);
        }
    }
}
