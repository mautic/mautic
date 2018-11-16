<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param \DateTime $executionDate
     */
    public function __construct(\DateTime $executionDate)
    {
        $this->executionDate = $executionDate;
        $this->contacts      = new ArrayCollection();
    }

    /**
     * @param Lead $contact
     */
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
