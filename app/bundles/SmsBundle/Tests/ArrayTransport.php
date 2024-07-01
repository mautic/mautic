<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;

class ArrayTransport implements TransportInterface
{
    /**
     * @var array<array{'contact': Lead, 'content': string}>
     */
    public array $smses = [];

    /**
     * @var array<array{'contact': Lead, 'content': string}>
     */
    public array $mmses = [];

    public function sendSms(Lead $lead, $content): bool
    {
        $this->smses[] = ['contact' => $lead, 'content' => $content];

        return true;
    }
}
