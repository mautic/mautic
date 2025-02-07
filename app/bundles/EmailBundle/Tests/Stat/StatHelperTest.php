<?php

namespace Mautic\EmailBundle\Tests\Stat;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\Lead;

class StatHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testStatsAreCreatedAndDeleted(): void
    {
        $emailStatmodel     = $this->createMock(EmailStatModel::class);
        $mockStatRepository = $this->createMock(StatRepository::class);

        $emailStatmodel->method('getRepository')->willReturn($mockStatRepository);

        $mockStatRepository->expects($this->once())
            ->method('deleteStats')
            ->withConsecutive([[1, 2, 3, 4, 5]]);

        $statHelper = new StatHelper($emailStatmodel);

        $mockEmail = $this->createMock(Email::class);
        $mockEmail->method('getId')
            ->willReturn(15);

        $counter = 1;
        while ($counter <= 5) {
            $stat = $this->createMock(Stat::class);

            $stat->method('getId')
                ->willReturn((string) $counter);

            $stat->method('getEmail')
                ->willReturn($mockEmail);

            $lead = $this->createMock(Lead::class);

            $lead->method('getId')
                ->willReturn($counter * 10);

            $stat->method('getLead')
                ->willReturn($lead);

            $emailAddress = "contact{$counter}@test.com";
            $statHelper->storeStat($stat, $emailAddress);

            // Delete it
            try {
                $reference = $statHelper->getStat($emailAddress);
                $this->assertEquals($reference->getLeadId(), $counter * 10);
                $statHelper->markForDeletion($reference);
            } catch (StatNotFoundException) {
                $this->fail("Stat not found for $emailAddress");
            }

            ++$counter;
        }

        $statHelper->deletePending();
    }

    public function testExceptionIsThrownIfEmailAddressIsNotFound(): void
    {
        $this->expectException(StatNotFoundException::class);

        $statHelper = new StatHelper($this->createMock(EmailStatModel::class));

        $statHelper->getStat('nada@nada.com');
    }
}
