<?php

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;

interface MMSTransportInterface
{
    /**
     * @param array<mixed> $media
     */
    public function sendMms(Lead $lead, string $content, array $media): bool|string;
}
