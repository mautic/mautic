<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Stats;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\EmailBundle\Stats\Helper\SentHelper;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Aggregate\Collector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SentHelperTest extends TestCase
{
    private Collector $collector;

    private DateTimeHelper $dateTimeHelper;

    private SentHelper $sentHelper;

    /**
     * @var MockObject|Connection
     */
    private MockObject $connection;

    /**
     * @var MockObject|GeneratedColumnsProviderInterface
     */
    private MockObject $generatedColumnsProvider;

    /**
     * @var MockObject|UserHelper
     */
    private MockObject $userHelperMock;

    /**
     * @var MockObject|QueryBuilder
     */
    private MockObject $queryBuilder;

    /**
     * @var MockObject|Result
     */
    private MockObject $result;

    private GeneratedColumns $generatedColumns;

    protected function setUp(): void
    {
        parent::setUp();

        $eventDispatcher                = new EventDispatcher();
        $this->collector                = new Collector($eventDispatcher);
        $this->connection               = $this->createMock(Connection::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->userHelperMock           = $this->createMock(UserHelper::class);
        $this->queryBuilder             = $this->createMock(QueryBuilder::class);
        $this->result                   = $this->createMock(Result::class);
        $this->dateTimeHelper           = new DateTimeHelper();

        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $generatedColumn        = new GeneratedColumn('email_stats', 'generated_sent_date', 'DATE', 'CONCAT(YEAR(date_sent), "-", LPAD(MONTH(date_sent), 2, "0"), "-", LPAD(DAY(date_sent), 2, "0"))');
        $this->generatedColumns = new GeneratedColumns();
        $generatedColumn->addIndexColumn('email_id');
        $generatedColumn->setOriginalDateColumn('date_sent', 'd');
        $this->generatedColumns->add($generatedColumn);

        $this->sentHelper = new SentHelper(
            $this->collector,
            $this->connection,
            $this->generatedColumnsProvider,
            $this->userHelperMock
        );
    }

    public function testGenerateStatsDaily(): void
    {
        $this->generatedColumnsProvider->expects($this->any())
            ->method('generatedColumnsAreSupported')
            ->willReturn(true);

        $this->generatedColumnsProvider->expects($this->once())
            ->method('getGeneratedColumns')
            ->willReturn($this->generatedColumns);

        $this->queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($this->result);

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with("DATE_FORMAT(CONVERT_TZ(t.generated_sent_date, '+00:00', '".$this->dateTimeHelper->getLocalTimezoneOffset()."'), '%Y-%m-%d') AS date, COUNT(*) AS count")
            ->willReturnSelf();

        $this->result->method('fetchAllAssociative')->willReturn([
            [
                'date'  => '2022-12-12',
                'count' => '60',
            ],
            [
                'date'  => '2022-12-16',
                'count' => '30',
            ],
        ]);

        $dateFrom = new \DateTime('2022-12-10 00:00:00');
        $dateTo   = new \DateTime('2022-12-20 00:00:00');
        $options  = new EmailStatOptions();
        $options->setEmailIds([17]);
        $options->setUnit('d');
        $options->setCanViewOthers(true);
        $statCollection = new StatCollection();
        $this->sentHelper->generateStats($dateFrom, $dateTo, $options, $statCollection);

        $days = $statCollection->getStats()->getDays();
        $this->assertEquals(60, $days['2022-12-12']->getSum());
        $this->assertEquals(30, $days['2022-12-16']->getSum());
    }

    public function testGenerateStatsHourly(): void
    {
        $this->generatedColumnsProvider->expects($this->any())
            ->method('generatedColumnsAreSupported')
            ->willReturn(true);

        $this->generatedColumnsProvider->expects($this->once())
            ->method('getGeneratedColumns')
            ->willReturn($this->generatedColumns);

        $this->queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($this->result);

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with("DATE_FORMAT(CONVERT_TZ(t.date_sent, '+00:00', '".$this->dateTimeHelper->getLocalTimezoneOffset()."'), '%Y-%m-%d %H:00') AS date, COUNT(*) AS count")
            ->willReturnSelf();

        $this->result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'date'  => '2022-12-10 13:00',
                    'count' => '12',
                ],
                [
                    'date'  => '2022-12-10 14:00',
                    'count' => '30',
                ],
            ]);

        $dateFrom = new \DateTime('2022-12-10 00:00:00');
        $dateTo   = new \DateTime('2022-12-11 00:00:00');
        $options  = new EmailStatOptions();
        $options->setEmailIds([17]);
        $options->setUnit('H');
        $options->setCanViewOthers(true);
        $statCollection = new StatCollection();
        $this->sentHelper->generateStats($dateFrom, $dateTo, $options, $statCollection);

        $hours = $statCollection->getStats()->getHours();

        $this->assertEquals(12, $hours['2022-12-10 13']->getCount());
        $this->assertEquals(30, $hours['2022-12-10 14']->getCount());
    }
}
