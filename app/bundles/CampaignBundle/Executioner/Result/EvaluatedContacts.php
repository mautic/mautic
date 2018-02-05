<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * ConditionContacts constructor.
     */
    public function __construct()
    {
        $this->passed = new ArrayCollection();
        $this->failed = new ArrayCollection();
    }

    /**
     * @param Lead $contact
     */
    public function pass(Lead $contact)
    {
        $this->passed->set($contact->getId(), $contact);
    }

    /**
     * @param Lead $contact
     */
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
