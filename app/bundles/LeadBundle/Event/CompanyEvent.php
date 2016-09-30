<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class CompanyEvent.
 */
class CompanyEvent extends CommonEvent
{
    /**
     * @param Company $lead
     * @param bool    $isNew
     */
    public function __construct(Company $company, $isNew = false)
    {
        $this->entity = $company;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getCompany()
    {
        return $this->entity;
    }

    /**
     * Sets the Lead entity.
     *
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->entity = $lead;
    }
}
