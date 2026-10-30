<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Mautic\LeadBundle\Entity\Lead;

interface EmailReplyRepositoryInterface
{
    /**
     * @param int|Lead $leadId
     */
    public function getByLeadIdForTimeline($leadId, array $options): array;
}
