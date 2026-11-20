<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;

interface TransportInterface
{
    /**
     * @param string $content
     *
     * @return bool|string true when accepted, otherwise false or a legacy provider error
     */
    public function sendSms(Lead $lead, $content);
}
