<?php

/*
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

/**
 * Class CompanyEvent.
 */
class CompanyEvent extends CommonEvent
{
    /**
     * @param Company $company
     * @param bool    $isNew
     * @param int     $score
     */
    public function __construct(Company $company, $isNew = false, $score = 0)
    {
        $this->entity = $company;
        $this->isNew  = $isNew;
        $this->score  = $score;
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
     *
     * @param Company $company
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
