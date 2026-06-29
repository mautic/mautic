<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model\AbTest;

use Mautic\EmailBundle\Entity\Email;

final class EmailStatus
{
    /**
     * @var array<int|string, mixed>
     */
    private array $children = [];

    private ?Email $parent  = null;

    private ?string $status = null;

    public function __construct(private Email $email, private string $publishStatus)
    {
        if ($email->isEnableAbTest()) {
            [$this->parent, $this->children] = $this->email->getVariants();

            if (!$this->hasChildren()) {
                $this->status = 'prepare';
            } elseif ($this->parent->isWinner()) {
                $this->status = 'done';
            } elseif ('pending' === $this->publishStatus) {
                // publish_up is set and in the future — scheduled but not yet started
                $this->status = 'pending';
            } elseif ($this->hasBeenStarted() && null !== $this->email->getPublishUp()) {
                // variant_start_date is set AND publish_up is set (and in the past) — actively running
                $this->status = 'running';
            } else {
                // No schedule set, or stale variant_start_date without publish_up — ready to schedule
                $this->status = 'prepare';
            }
        }
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    private function hasBeenStarted(): bool
    {
        return null !== $this->parent->getVariantStartDate();
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
