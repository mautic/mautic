<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode\DAO;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;

class GroupExecutionDateDAO
{
    /**
     * @var \DateTime
     */
    private $executionDate;

    /**
     * @var ArrayCollection
     */
    private $contacts;

    /**
     * GroupExecutionDateDAO constructor.
     */
    public function __construct(\DateTime $executionDate)
    {
        $this->executionDate = $executionDate;
        $this->contacts      = new ArrayCollection();
    }

    public function addContact(Lead $contact)
    {
        $this->contacts->set($contact->getId(), $contact);
    }

    /**
     * @return \DateTime
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
