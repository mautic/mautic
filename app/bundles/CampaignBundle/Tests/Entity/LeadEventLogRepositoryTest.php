<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use PHPUnit\Framework\TestCase;

final class LeadEventLogRepositoryTest extends TestCase
{
    /**
     * @dataProvider isLastFailedDataProvider
     */
    public function testIsLastFailed(?LeadEventLog $leadEventLog, bool $expectedResult): void
    {
        $emMock                 = $this->createMock(EntityManager::class);
        $class                  = new ClassMetadata(LeadEventLog::class);
        $leadEventLogRepository = new LeadEventLogRepository($emMock, $class);

        $unitOfWorkMock = $this->createMock(UnitOfWork::class);
        $emMock->expects($this->at(0))
            ->method('getUnitOfWork')
            ->willReturn($unitOfWorkMock);
        $entityPersisterMock = $this->createMock(EntityPersister::class);
        $unitOfWorkMock->expects($this->at(0))
            ->method('getEntityPersister')
            ->willReturn($entityPersisterMock);
        $entityPersisterMock->expects($this->at(0))
            ->method('load')
            ->with(['lead' => 42, 'event' => 4242], null, null, [], null, 1, ['dateTriggered' => 'DESC'])
            ->willReturn($leadEventLog);
        $isLastFailed = $leadEventLogRepository->isLastFailed(42, 4242);
        $this->assertSame($expectedResult, $isLastFailed);
    }

    public function isLastFailedDataProvider(): array
    {
        $leadEventLogNoFail = new LeadEventLog();
        $failedLeadEvent    = new FailedLeadEventLog();
        $leadEventLogFail   = new LeadEventLog();
        $leadEventLogFail->setFailedLog($failedLeadEvent);

        return [
            'no_last_log'      => [null, false],
            'last_log_no_fail' => [$leadEventLogNoFail, false],
            'last_log_fail'    => [$leadEventLogFail, true],
        ];
    }
}
