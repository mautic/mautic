<?php

namespace Mautic\CampaignBundle\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;

class EvaluatedContacts
{
    /**
     * @var ArrayCollection
     */
    private $passed;

    /**
     * @var ArrayCollection
     */
    private $failed;

    /**
     * EvaluatedContacts constructor.
     */
    public function __construct(ArrayCollection $passed = null, ArrayCollection $failed = null)
    {
        $this->passed = (null === $passed) ? new ArrayCollection() : $passed;
        $this->failed = (null === $failed) ? new ArrayCollection() : $failed;
    }

    public function pass(Lead $contact)
    {
        $this->passed->set($contact->getId(), $contact);
    }

    public function fail(Lead $contact)
    {
        $this->failed->set($contact->getId(), $contact);
    }

    /**
     * @return ArrayCollection|Lead[]
     */
    public function getPassed()
    {
        return $this->passed;
    }

    /**
     * @return ArrayCollection|Lead[]
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
