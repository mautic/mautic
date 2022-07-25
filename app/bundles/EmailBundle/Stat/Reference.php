<?php

namespace Mautic\EmailBundle\Stat;

use Mautic\EmailBundle\Entity\Stat;

class Reference
{
    /**
     * @var int
     */
    private $emailId;

    /**
     * @var int
     */
    private $leadId = 0;

    /**
     * @var int|null
     */
    private $statId;

    public function __construct(Stat $stat)
    {
        $this->statId  = $stat->getId();
        $this->emailId = $stat->getEmail()->getId();
        if ($lead = $stat->getLead()) {
            $this->leadId = $stat->getLead()->getId();
        }
    }

    /**
     * @return int
     */
    public function getEmailId()
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

    /**
     * @return mixed
     */
    public function getStatId()
    {
        return $this->statId;
    }
}
