<?php

namespace Mautic\EmailBundle\Stat;

use Mautic\EmailBundle\Entity\Stat;

final class Reference
{
    private readonly ?int $emailId;

    private int $leadId = 0;

    private readonly ?string $statId;

    public function __construct(Stat $stat)
    {
        $this->statId  = $stat->getId();
        $this->emailId = $stat->getEmail()->getId();
        if ($lead = $stat->getLead()) {
            $this->leadId = $lead->getId();
        }
    }

    public function getEmailId(): ?int
    {
        return $this->emailId;
    }

    public function getLeadId(): int
    {
        return $this->leadId;
    }

    public function getStatId(): ?string
    {
        return $this->statId;
    }
}
