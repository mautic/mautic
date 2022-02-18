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
     * @var ArrayCollection<int, Lead>
     */
    private $passed;

    /**
     * @var ArrayCollection<int, Lead>
     */
    private $failed;

    /**
     * @param ArrayCollection<int, Lead> $passed
     * @param ArrayCollection<int, Lead> $failed
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
     * @return ArrayCollection<int, Lead>
     */
    public function getPassed()
    {
        return $this->passed;
    }

    /**
     * @return ArrayCollection<int, Lead>
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
