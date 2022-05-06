<?php

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;

interface TransportInterface
{
    /**
     * @param string $content
     * @param Sms    $sms
     * @param Stat   $stat
     *
     * @return bool
     */
    public function sendSms(Lead $lead, $content);
}
