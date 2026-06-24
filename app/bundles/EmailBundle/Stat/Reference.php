<?php

namespace Mautic\EmailBundle\Stat;

use Mautic\EmailBundle\Entity\Stat;

class Reference
{
    private ?int $emailId;

    /**
     * @var int
     */
    private $leadId = 0;

    private ?string $statId;

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

    /**
     * @return int
     */
    public function getLeadId()
    {
        return $this->leadId;
    }

    public function getStatId(): ?string
    {
        return $this->statId;
    }
}
