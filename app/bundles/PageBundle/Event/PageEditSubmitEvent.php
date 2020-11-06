<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageEditSubmitEvent.
 */
class PageEditSubmitEvent extends CommonEvent
{
    /**
     * @var Page
     */
    private $previousPage;
    /**
     * @var Page
     */
    private $currentPage;
    /**
     * @var bool
     */
    private $saveAndClose;
    /**
     * @var bool
     */
    private $apply;
    /**
     * @var bool
     */
    private $saveAsDraft;
    /**
     * @var bool
     */
    private $applyDraft;
    /**
     * @var bool
     */
    private $discardDraft;

    public function __construct(
        Page $previousPage,
        Page $currentPage,
        bool $saveAndClose,
        bool $apply,
        bool $saveAsDraft,
        bool $applyDraft,
        bool $discardDraft
    ) {
        $this->previousPage  = $previousPage;
        $this->currentPage   = $currentPage;
        $this->saveAndClose  = $saveAndClose;
        $this->apply         = $apply;
        $this->saveAsDraft   = $saveAsDraft;
        $this->applyDraft    = $applyDraft;
        $this->discardDraft  = $discardDraft;
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
