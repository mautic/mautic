<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Company;

class CompanyEvent extends CommonEvent
{
    public function __construct(
        Company $company,
        bool $isNew = false,
        protected int $score = 0,
    ) {
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
    public function setCompany(Company $company): void
    {
        $this->entity = $company;
    }

    public function changeScore(int $score): void
    {
        $this->score = $score;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
