<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LeadCompanyChangeEvent.
 */
class LeadChangeCompanyEvent extends Event
{
    private $lead;
    private $leads;
    private $company;
    private $added;

    /**
     * @param Lead    $lead
     * @param Company $company
     */
    public function __construct($leads, Company $company, $added = true)
    {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
        $this->company = $company;
        $this->added   = $added;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Returns batch array of leads.
     *
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @return Company/Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return bool
     */
    public function wasAdded()
    {
        return $this->added;
    }

    /**
     * @return bool
     */
    public function wasRemoved()
    {
        return !$this->added;
    }
}
