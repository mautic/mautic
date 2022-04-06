<?php

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\TrackingSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;

class TrackingSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|StatRepository
     */
    private $statRepository;

    protected function setUp(): void
    {
        $this->statRepository = $this->createMock(StatRepository::class);
    }

    public function testIdentifyContactByStat()
    {
        $ct = [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
        ];

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn(1);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(2);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($lead);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertEquals($lead->getId(), $event->getIdentifiedContact()->getId());
    }

    public function testChannelMismatchDoesNotIdentify()
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'sms' => 1,
            ],
            'stat'    => 'abc123',
        ];

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNull($event->getIdentifiedContact());
    }

    public function testChannelIdMismatchDoesNotIdentify()
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'email' => 2,
            ],
            'stat'    => 'abc123',
        ];

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn(1);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')
            ->willReturn(2);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($lead);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNull($event->getIdentifiedContact());
    }

    public function testStatEmptyLeadDoesNotIdentify()
    {
        $ct = [
            'lead'    => 2,
            'channel' => [
                'email' => 2,
            ],
            'stat'    => 'abc123',
        ];

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn(1);

        $stat = new Stat();
        $stat->setEmail($email);

        $this->statRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['trackingHash' => 'abc123'])
            ->willReturn($stat);

        $event = new ContactIdentificationEvent($ct);

        $this->getSubscriber()->onIdentifyContact($event);

        $this->assertNull($event->getIdentifiedContact());
    }

    /**
     * @return TrackingSubscriber
     */
    private function getSubscriber()
    {
        return new TrackingSubscriber($this->statRepository);
    }
}
