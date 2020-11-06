<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;

class EmailEditSubmitEvent extends CommonEvent
{
    /**
     * @var Email
     */
    private $previousEmail;
    /**
     * @var Email
     */
    private $currentEmail;
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
        Email $previousEmail,
        Email $currentEmail,
        bool $saveAndClose,
        bool $apply,
        bool $saveAsDraft,
        bool $applyDraft,
        bool $discardDraft
    ) {
        $this->previousEmail = $previousEmail;
        $this->currentEmail  = $currentEmail;
        $this->saveAndClose  = $saveAndClose;
        $this->apply         = $apply;
        $this->saveAsDraft   = $saveAsDraft;
        $this->applyDraft    = $applyDraft;
        $this->discardDraft  = $discardDraft;
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
