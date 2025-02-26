<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\TestCase;

final class LeadEventLogRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    /**
     * @dataProvider isLastFailedDataProvider
     */
    public function testIsLastFailed(?LeadEventLog $leadEventLog, bool $expectedResult): void
    {
        $emMock                 = $this->createMock(EntityManager::class);
        $unitOfWorkMock         = $this->createMock(UnitOfWork::class);
        $emMock->method('getUnitOfWork')
            ->willReturn($unitOfWorkMock);

        $entityPersisterMock = $this->createMock(EntityPersister::class);
        $unitOfWorkMock->method('getEntityPersister')
            ->willReturn($entityPersisterMock);

        $entityPersisterMock->method('load')
            ->with(['lead' => 42, 'event' => 4242], null, null, [], null, 1, ['dateTriggered' => 'DESC'])
            ->willReturn($leadEventLog);

        $leadEventLogRepository = $this->configureRepository(LeadEventLog::class, $emMock);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));

        $isLastFailed = $leadEventLogRepository->isLastFailed(42, 4242);
        $this->assertSame($expectedResult, $isLastFailed);
    }

    /**
     * @return array<string,array<mixed>>
     */
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
