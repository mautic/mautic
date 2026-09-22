<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadListRepository;

final class MatchFilterForLeadTraitTestable
{
    use MatchFilterForLeadTrait;

    private LeadListRepository $segmentRepository;

    public function setRepository(LeadListRepository $segmentRepository): void
    {
        $this->segmentRepository = $segmentRepository;
    }

    /**
     * @param array<int, array<string, mixed>> $filter
     * @param array<string, mixed>             $lead
     */
    public function match(array $filter, array $lead): bool
    {
        return $this->matchFilterForLead($filter, $lead);
    }
}
