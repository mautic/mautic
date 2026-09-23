<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Company;

final class CompanyEvent extends CommonEvent
{
    public function __construct(
        Company $company,
        bool $isNew = false,
        private int $score = 0,
    ) {
        $this->entity = $company;
        $this->isNew  = $isNew;
    }

    public function getCompany(): Company
    {
        return $this->entity;
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
