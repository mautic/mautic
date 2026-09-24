<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode\DAO;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\LeadBundle\Entity\Lead;

final readonly class GroupExecutionDateDAO
{
    /**
     * @var Collection<int, Lead>
     */
    private Collection $contacts;

    public function __construct(
        private \DateTimeInterface $executionDate,
    ) {
        $this->contacts = new ArrayCollection();
    }

    public function addContact(Lead $contact): void
    {
        $this->contacts->set($contact->getId(), $contact);
    }

    public function getExecutionDate(): \DateTimeInterface
    {
        return $this->executionDate;
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }
}
