<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;

interface TransportInterface
{
    /**
     * @param string $content
     *
     * @return bool
     */
    public function sendSms(Lead $lead, $content);
}
