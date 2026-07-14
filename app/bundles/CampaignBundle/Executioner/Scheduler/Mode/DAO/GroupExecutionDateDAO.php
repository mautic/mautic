<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode\DAO;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;

class GroupExecutionDateDAO
{
    private readonly ArrayCollection $contacts;

    public function __construct(
        private readonly \DateTimeInterface $executionDate,
    ) {
        $this->contacts      = new ArrayCollection();
    }

    public function addContact(Lead $contact): void
    {
        $this->contacts->set($contact->getId(), $contact);
    }

    public function getExecutionDate(): \DateTimeInterface
    {
        return $this->executionDate;
    }

    public function getContacts(): ArrayCollection
    {
        return $this->contacts;
    }
}
