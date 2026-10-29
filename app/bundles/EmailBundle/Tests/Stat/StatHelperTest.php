<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Stat;

use PHPUnit\Framework\TestCase;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class StatHelperTest extends TestCase
{
    public function testStatsAreCreatedAndDeleted(): void
    {
        $emailStatmodel     = $this->createStub(EmailStatModel::class);
        $mockStatRepository = $this->createMock(StatRepository::class);

        $mockStatRepository->expects($this->once())
            ->method('deleteStats')
            ->with([1, 2, 3, 4, 5]);

        $statHelper = new StatHelper($emailStatmodel, $mockStatRepository);

        $mockEmail = new Email();
        $mockEmail->setId(15);

        $counter = 1;
        while ($counter <= 5) {
            $stat = new Stat();

            ReflectionHelper::setEntityId($stat, (string) $counter);

            $stat->setEmail($mockEmail);

            $lead = new Lead();
            $lead->setId($counter * 10);

            $stat->setLead($lead);

            $emailAddress = "contact{$counter}@test.com";
            $statHelper->storeStat($stat, $emailAddress);

            // Delete it
            try {
                $reference = $statHelper->getStat($emailAddress);
                $this->assertEquals($reference->getLeadId(), $counter * 10);
                $statHelper->markForDeletion($reference);
            } catch (StatNotFoundException) {
                $this->fail("Stat not found for {$emailAddress}");
            }

            ++$counter;
        }

        $statHelper->deletePending();
    }

    public function testExceptionIsThrownIfEmailAddressIsNotFound(): void
    {
        $this->expectException(StatNotFoundException::class);

        $statHelper = new StatHelper($this->createStub(EmailStatModel::class), $this->createStub(StatRepository::class));

        $statHelper->getStat('nada@nada.com');
    }
}
