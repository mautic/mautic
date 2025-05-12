<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Search;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Monolog\Logger;

#[\PHPUnit\Framework\Attributes\CoversClass(ContactFinder::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\EmailBundle\MonitoredEmail\Search\Result::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\EmailBundle\MonitoredEmail\Processor\Address::class)]
class ContactFinderTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Contact should be found via contact email address')]
    public function testContactFoundByDelegationForAddress(): void
    {
        $lead = new Lead();
        $lead->setEmail('contact@email.com');

        $statRepository = $this->createMock(StatRepository::class);
        $statRepository->expects($this->never())
            ->method('findOneBy');

        $leadRepository = $this->createMock(LeadRepository::class);
        $leadRepository->expects($this->once())
            ->method('getContactsByEmail')
            ->willReturn([$lead]);

        $logger = $this->createMock(Logger::class);

        $finder = new ContactFinder($statRepository, $leadRepository, $logger);
        $result = $finder->find($lead->getEmail(), 'contact@test.com');

        $this->assertEquals($result->getContacts(), [$lead]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Contact should be found via a hash in to email address')]
    public function testContactFoundByDelegationForHash(): void
    {
        $lead = new Lead();
        $lead->setEmail('contact@email.com');

        $stat = new Stat();
        $stat->setLead($lead);

        $statRepository = $this->createMock(StatRepository::class);
        $statRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturnCallback(
                function ($hash) use ($stat) {
                    $stat->setTrackingHash($hash);

                    $email = new Email();
                    $stat->setEmail($email);

                    return $stat;
                }
            );

        $leadRepository = $this->createMock(LeadRepository::class);
        $leadRepository->expects($this->never())
            ->method('getContactsByEmail');

        $logger = $this->createMock(Logger::class);

        $finder = new ContactFinder($statRepository, $leadRepository, $logger);
        $result = $finder->find($lead->getEmail(), 'test+unsubscribe_123abc@test.com');

        $this->assertEquals($result->getStat(), $stat);
        $this->assertEquals($result->getContacts(), [$lead]);
    }
}
