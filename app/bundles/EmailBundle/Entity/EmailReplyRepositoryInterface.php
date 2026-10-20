<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Mautic\LeadBundle\Entity\Lead;

interface EmailReplyRepositoryInterface
{
    /**
     * @param int|Lead $leadId
     * @param array    $options
     */
    public function getByLeadIdForTimeline($leadId, $options): array;
}
