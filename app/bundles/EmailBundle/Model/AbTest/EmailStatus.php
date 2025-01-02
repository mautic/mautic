<?php

namespace Mautic\EmailBundle\Model\AbTest;

use Mautic\EmailBundle\Entity\Email;

class EmailStatus
{
    private array $children;
    private Email $parent;
    private ?string $status = null;

    public function __construct(private Email $email, int $pendingCount, private string $publishStatus)
    {
        if ($email->isEnableAbTest()) {
            [$this->parent, $this->children] = $this->email->getVariants();
            $variantsPendingCount = $this->parent ? $this->parent->getVariantsPendingCount($pendingCount) : 0;

            if (!$this->hasChildren()) {
                $this->status = 'prepare';
            } elseif (!$this->hasBeenStarted() && in_array($this->email->getPublishStatus(), ['published', 'unpublished'])) {
                $this->status = $this->email->getPublishUp() ? 'running' : 'prepare';
            } elseif ($this->publishStatus === 'pending') {
                $this->status = 'pending';
            } elseif ($this->publishStatus === 'running') {
                if ($this->email->waitingToDetermineWinner($variantsPendingCount) || $this->email->waitingToSendTestsEmails($variantsPendingCount)) {
                    $this->status = 'running';
                }
            } elseif ($this->email->waitingToDetermineWinner($variantsPendingCount) || $this->email->waitingToSendTestsEmails($variantsPendingCount)) {
                $this->status = 'sending';
            } elseif ($this->parent->isWinner()) {
                $this->status = 'done';
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
