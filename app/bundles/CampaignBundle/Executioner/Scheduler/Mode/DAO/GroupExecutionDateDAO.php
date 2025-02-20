<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode\DAO;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;

class GroupExecutionDateDAO
{
    private ArrayCollection $contacts;

    public function __construct(
        private \DateTimeInterface $executionDate,
    ) {
        $this->contacts      = new ArrayCollection();
    }

    public function addContact(Lead $contact): void
    {
        $this->contacts->set($contact->getId(), $contact);
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExecutionDate()
    {
        return $this->executionDate;
    }

    /**
     * @return ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }
}
