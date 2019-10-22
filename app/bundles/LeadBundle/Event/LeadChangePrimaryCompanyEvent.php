<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
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
 * Class LeadChangePrimaryCompanyEvent.
 */
class LeadChangePrimaryCompanyEvent extends Event
{
    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var int
     */
    private $oldPrimaryCompanyId;

    /**
     * @var int
     */
    private $newPrimaryCompanyId;

    /**
     * @param Lead    $lead
     * @param Company $company
     */
    public function __construct(Lead $lead, int $oldPrimaryCompanyId, int $newPrimaryCompanyId)
    {
        $this->lead                = $lead;
        $this->oldPrimaryCompanyId = $oldPrimaryCompanyId;
        $this->newPrimaryCompanyId = $newPrimaryCompanyId;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return int
     */
    public function getOldPrimaryCompanyId()
    {
        return $this->oldPrimaryCompanyId;
    }

    /**
     * @return int
     */
    public function getNewPrimaryCompanyId()
    {
        return $this->newPrimaryCompanyId;
    }
}
