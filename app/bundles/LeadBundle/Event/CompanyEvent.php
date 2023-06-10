<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Company;

/**
 * Class CompanyEvent.
 */
class CompanyEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     * @param int  $score
     */
    public function __construct(Company $company, $isNew = false, protected $score = 0)
    {
        $this->entity = $company;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Company entity.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->entity;
    }

    /**
     * Sets the Company entity.
     */
    public function setCompany(Company $company)
    {
        $this->entity = $company;
    }

    public function changeScore($score)
    {
        $this->score = $score;
    }

    public function getScore()
    {
        return $this->score;
    }
}
