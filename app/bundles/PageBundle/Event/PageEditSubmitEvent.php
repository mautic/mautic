<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

final class PageEditSubmitEvent extends CommonEvent
{
    public function __construct(
        private readonly Page $previousPage,
        private readonly Page $currentPage,
        private readonly bool $saveAndClose,
        private readonly bool $apply,
        private readonly bool $saveAsDraft,
        private readonly bool $applyDraft,
        private readonly bool $discardDraft,
    ) {
    }

    public function getPreviousPage(): Page
    {
        return $this->previousPage;
    }

    public function getCurrentPage(): Page
    {
        return $this->currentPage;
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
