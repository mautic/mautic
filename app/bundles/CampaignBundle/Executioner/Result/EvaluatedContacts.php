<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\LeadBundle\Entity\Lead;

final readonly class EvaluatedContacts
{
    /**
     * @var Collection<int, Lead>
     */
    private Collection $passed;

    /**
     * @var Collection<int, Lead>
     */
    private Collection $failed;

    /**
     * @param Collection<int, Lead>|null $passed
     * @param Collection<int, Lead>|null $failed
     */
    public function __construct(?Collection $passed = null, ?Collection $failed = null)
    {
        $this->passed = $passed ?? new ArrayCollection();
        $this->failed = $failed ?? new ArrayCollection();
    }

    public function pass(Lead $contact): void
    {
        $this->passed->set($contact->getId(), $contact);
    }

    public function fail(Lead $contact): void
    {
        $this->failed->set($contact->getId(), $contact);
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getPassed(): Collection
    {
        return $this->passed;
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getFailed(): Collection
    {
        return $this->failed;
    }
}
