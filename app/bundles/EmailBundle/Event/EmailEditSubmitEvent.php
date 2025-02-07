<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;

class EmailEditSubmitEvent extends CommonEvent
{
    public function __construct(
        private Email $previousEmail,
        private Email $currentEmail,
        private bool $saveAndClose,
        private bool $apply,
        private bool $saveAsDraft,
        private bool $applyDraft,
        private bool $discardDraft,
    ) {
    }

    public function getPreviousEmail(): Email
    {
        return $this->previousEmail;
    }

    public function getCurrentEmail(): Email
    {
        return $this->currentEmail;
    }

    public function isSaveAndClose(): bool
    {
        return $this->saveAndClose;
    }

    public function isApply(): bool
    {
        return $this->apply;
    }

    public function isSaveAsDraft(): bool
    {
        return $this->saveAsDraft;
    }

    public function isApplyDraft(): bool
    {
        return $this->applyDraft;
    }

    public function isDiscardDraft(): bool
    {
        return $this->discardDraft;
    }
}
