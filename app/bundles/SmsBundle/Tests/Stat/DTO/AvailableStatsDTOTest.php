<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Stat\DTO;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use Mautic\SmsBundle\Stat\DTO\AvailableStatsDTO;
use Mautic\SmsBundle\Stat\Interface\DeliverySupportInterface;
use Mautic\SmsBundle\Stat\Interface\FailedSupportInterface;
use Mautic\SmsBundle\Stat\Interface\ReadSupportInterface;
use PHPUnit\Framework\TestCase;

class AvailableStatsDTOTest extends TestCase
{
    public function testHasDeliveryStats(): void
    {
        $transport         = $this->getTransportWithSupportInterfaces();
        $availableStatsDTO = new AvailableStatsDTO($transport);

        $this->assertTrue($availableStatsDTO->hasDeliveryStats());
    }

    public function testHasReadStats(): void
    {
        $transport         = $this->getTransportWithSupportInterfaces();
        $availableStatsDTO = new AvailableStatsDTO($transport);

        $this->assertTrue($availableStatsDTO->hasReadStats());
    }

    public function testHasFailedStats(): void
    {
        $transport         = $this->getTransportWithSupportInterfaces();
        $availableStatsDTO = new AvailableStatsDTO($transport);

        $this->assertTrue($availableStatsDTO->hasFailedStats());
    }

    public function testDoesNotHaveUnsupportedStats(): void
    {
        $transport         = $this->createMock(TransportInterface::class);
        $availableStatsDTO = new AvailableStatsDTO($transport);

        $this->assertFalse($availableStatsDTO->hasDeliveryStats());
        $this->assertFalse($availableStatsDTO->hasReadStats());
        $this->assertFalse($availableStatsDTO->hasFailedStats());
    }

    protected function getTransportWithSupportInterfaces(): TransportInterface|DeliverySupportInterface|ReadSupportInterface|FailedSupportInterface
    {
        $transport = new class() implements TransportInterface, DeliverySupportInterface, ReadSupportInterface, FailedSupportInterface {
            public function sendSms(Lead $lead, $content)
            {
                return true;
            }
        };

        return $transport;
    }
}
