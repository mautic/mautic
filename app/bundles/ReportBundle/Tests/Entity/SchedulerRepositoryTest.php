<?php

namespace Mautic\ReportBundle\Tests\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use PHPUnit\Framework\MockObject\MockObject;

class SchedulerRepositoryTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    public function testGetScheduledReportsForExportNoID(): void
    {
        $schedulerRepository = $this->configureRepository(Scheduler::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with()
            ->willReturn($this->getQueryBuilderMock());

        $result = $schedulerRepository->getScheduledReportsForExport(new ExportOption(null));

        $this->assertSame([], $result);
    }

    /**
     * @return QueryBuilder|MockObject
     */
    private function getQueryBuilderMock()
    {
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock->expects($this->once())
            ->method('select')
            ->with('scheduler')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('from')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('addSelect')
            ->with('report')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('leftJoin')
            ->with('scheduler.report', 'report')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('andWhere')
            ->with('scheduler.scheduleDate <= :scheduleDate')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('scheduleDate', $this->callback(function ($date) {
                $today = new \DateTime();
                $today->modify('+1 seconds'); // make sure our date is bigger

                return $date instanceof \DateTime && $date < $today;
            }))
            ->willReturn($queryBuilderMock);

        $abstractQueryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock->expects($this->once())
            ->method('getQuery')
            ->with()
            ->willReturn($abstractQueryMock);

        $abstractQueryMock->expects($this->once())
            ->method('getResult')
            ->with()
            ->willReturn([]);

        return $queryBuilderMock;
    }
}
