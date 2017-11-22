<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class SchedulerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetScheduledReportsForExportNoID()
    {
        $entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadataMock = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock = $this->getQueryBuilderMock();

        $entityManagerMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with()
            ->willReturn($queryBuilderMock);

        $schedulerRepository = new SchedulerRepository($entityManagerMock, $classMetadataMock);

        $exportOption = new ExportOption(null);

        $result = $schedulerRepository->getScheduledReportsForExport($exportOption);

        $this->assertSame([], $result);
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
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
                $today->modify('+1 seconds'); //make sure our date is bigger

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
