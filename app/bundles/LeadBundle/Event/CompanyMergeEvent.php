<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;

final class CompanyMergeEvent extends Event
{
    public function __construct(
        private Company $victor,
        private Company $loser
    ) {
    }

    /**
     * Returns the victor (loser merges into the victor).
     */
    public function getVictor(): Company
    {
        return $this->victor;
    }

    /**
     * Returns the loser (loser merges into the victor).
     */
    public function getLoser(): Company
    {
        return $this->loser;
    }
}
