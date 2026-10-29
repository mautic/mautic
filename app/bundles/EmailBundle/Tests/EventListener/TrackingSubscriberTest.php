<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Entity\StatRepository;
use Mautic\SmsBundle\EventListener\TrackingSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class TrackingSubscriberTest extends TestCase
{
    /**
     * @var MockObject&StatRepository
     */
    private MockObject $statRepository;

    protected function setUp(): void
    {
        $this->statRepository = $this->createMock(StatRepository::class);
    }

    public function testIdentifyContactByStat(): void
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'sms' => 1,
            ],
            'stat'    => 'abc123',
        ];

        $sms = new Sms();
        ReflectionHelper::setEntityId($sms, 1);

        $lead = new Lead();
        $lead->setId(2);

        $stat = new Stat();
        $stat->setSms($sms);
        $stat->setLead($lead);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertSame($lead->getId(), $event->getIdentifiedContact()->getId());
    }

    public function testChannelMismatchDoesNotIdentify(): void
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'email' => 1,
            ],
            'stat'    => 'abc123',
        ];

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNotInstanceOf(Lead::class, $event->getIdentifiedContact());
    }

    public function testChannelIdMismatchDoesNotIdentify(): void
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'sms' => 2,
            ],
            'stat'    => 'abc123',
        ];

        $sms = new Sms();
        ReflectionHelper::setEntityId($sms, 1);

        $lead = new Lead();
        $lead->setId(2);

        $stat = new Stat();
        $stat->setSms($sms);
        $stat->setLead($lead);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNotInstanceOf(Lead::class, $event->getIdentifiedContact());
    }

    public function testStatEmptyLeadDoesNotIdentify(): void
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'sms' => 2,
            ],
            'stat'    => 'abc123',
        ];

        $sms = new Sms();
        ReflectionHelper::setEntityId($sms, 1);

        $stat = new Stat();
        $stat->setSms($sms);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNotInstanceOf(Lead::class, $event->getIdentifiedContact());
    }

    private function getSubscriber(): TrackingSubscriber
    {
        return new TrackingSubscriber($this->statRepository);
    }
}
