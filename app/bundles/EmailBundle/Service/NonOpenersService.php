<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Service;

use Mautic\CoreBundle\Helper\CommandHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;

class NonOpenersService
{
    public function __construct(
        private EmailModel $emailModel,
        private ListModel $listModel,
        private CommandHelper $commandHelper,
    ) {
    }

    public function canResend(Email $email): bool
    {
        // Always check the translation parent
        if ($translationParent = $email->getTranslationParent()) {
            $email = $translationParent;
        }

        if ('list' !== $email->getEmailType()) {
            return false;
        }

        if ('sent' !== $email->getSendingStatus()) {
            return false;
        }

        if ($email->getResends()->count() > 0) {
            return false;
        }

        if ($email->isResend()) {
            return false;
        }

        return true;
    }

    /**
     * @return array{emailId: int, segmentIds: list<int>}
     */
    public function resend(int $originalEmailId): array
    {
        $email = $this->emailModel->getEntity($originalEmailId);

        if (!$email instanceof Email) {
            throw new \InvalidArgumentException(sprintf('Email with ID %d not found.', $originalEmailId));
        }

        // Always work from the translation parent (segments are assigned to the parent)
        if ($translationParent = $email->getTranslationParent()) {
            $email           = $translationParent;
            $originalEmailId = $email->getId();
        }

        if (!$this->canResend($email)) {
            throw new \LogicException(sprintf('Email with ID %d cannot be resent.', $originalEmailId));
        }

        if ($email->getLists()->isEmpty()) {
            throw new \LogicException('Original email has no segments assigned.');
        }

        // Collect all email IDs to exclude (original + translation children)
        $emailIdsToExclude = [$originalEmailId];

        foreach ($email->getTranslationChildren() as $child) {
            $emailIdsToExclude[] = $child->getId();
        }

        // Create a new segment that combines:
        // 1. Membership in the original segment(s) (ensures we only target the original audience)
        // 2. "Not read email" filter (identifies non-openers)
        $originalSegmentIds   = [];
        $originalSegmentNames = [];
        foreach ($email->getLists() as $originalSegment) {
            $originalSegmentIds[]   = $originalSegment->getId();
            $originalSegmentNames[] = $originalSegment->getName();
        }

        $newSegment = new LeadList();
        $newSegment->setName(implode(', ', $originalSegmentNames).' (Non-Openers Resend, Email #'.$email->getId().')');
        $newSegment->setDescription('Auto-generated for resend to non-openers of: '.$email->getName().' (ID '.$email->getId().')');
        $newSegment->setIsGlobal(false);
        $newSegment->setIsPublished(true);

        $newSegment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'leadlist',
                'object'     => 'lead',
                'type'       => 'leadlist',
                'operator'   => 'in',
                'properties' => [
                    'filter' => $originalSegmentIds,
                ],
            ],
            [
                'glue'       => 'and',
                'field'      => 'lead_email_received',
                'object'     => 'behaviors',
                'type'       => 'lead_email_received',
                'operator'   => '!in',
                'properties' => [
                    'filter' => $emailIdsToExclude,
                ],
            ],
        ]);

        $this->listModel->saveEntity($newSegment);
        $clonedSegments = [$newSegment];

        // Rebuild all cloned segments
        foreach ($clonedSegments as $segment) {
            $this->commandHelper->runCommand('mautic:segments:update', ['-i' => $segment->getId()]);
        }

        // Clone the parent email
        $clonedEmail = clone $email;
        $clonedEmail->setEmailType('list');
        $clonedEmail->setResendOf($email);
        $clonedEmail->setName($email->getName().' (Resend - Non-Openers)');
        $clonedEmail->setLists($clonedSegments);
        $clonedEmail->setIsPublished(true);

        $this->emailModel->saveEntity($clonedEmail);

        // Clone each translation child
        foreach ($email->getTranslationChildren() as $child) {
            $clonedChild = clone $child;
            $clonedChild->setEmailType('list');
            $clonedChild->setName($child->getName().' (Resend - Non-Openers)');
            $clonedChild->setTranslationParent($clonedEmail);
            $clonedChild->setIsPublished(true);

            $this->emailModel->saveEntity($clonedChild);
        }

        return [
            'emailId'    => $clonedEmail->getId(),
            'segmentIds' => array_map(fn (LeadList $s) => $s->getId(), $clonedSegments),
        ];
    }
}
