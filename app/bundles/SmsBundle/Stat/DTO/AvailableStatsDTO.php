<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Stat\DTO;

use Mautic\SmsBundle\Sms\TransportInterface;
use Mautic\SmsBundle\Stat\Interface\DeliverySupportInterface;
use Mautic\SmsBundle\Stat\Interface\FailedSupportInterface;
use Mautic\SmsBundle\Stat\Interface\ReadSupportInterface;

class AvailableStatsDTO
{
    public function __construct(private TransportInterface $transport)
    {
    }

    public function hasDeliveryStats(): bool
    {
        return $this->transport instanceof DeliverySupportInterface;
    }

    public function hasReadStats(): bool
    {
        return $this->transport instanceof ReadSupportInterface;
    }

    public function hasFailedStats(): bool
    {
        return $this->transport instanceof FailedSupportInterface;
    }
}
