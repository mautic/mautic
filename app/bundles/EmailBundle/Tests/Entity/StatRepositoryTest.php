<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;

final class StatRepositoryTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    private StatRepository $statRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statRepository = $this->configureRepository(Stat::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));
    }

    public function testGetStatsSummaryForContacts(): void
    {
        $leadIdCol              = $this->connection->quoteIdentifier('lead_id');
        $sentCountCol           = $this->connection->quoteIdentifier('sent_count');
        $readCountCol           = $this->connection->quoteIdentifier('read_count');
        $clickedThroughCountCol = $this->connection->quoteIdentifier('clicked_through_count');

        $expectedQuery = "SELECT l.id AS $leadIdCol, COUNT(es.id) AS $sentCountCol, SUM(COALESCE(es.is_read, 0)) AS $readCountCol, SUM(COALESCE(sq.hits, 0)) AS $clickedThroughCountCol FROM ".MAUTIC_TABLE_PREFIX.'email_stats es RIGHT JOIN '.MAUTIC_TABLE_PREFIX.'leads l ON es.lead_id=l.id LEFT JOIN (SELECT COUNT(ph.id) AS hits, COUNT(DISTINCT(ph.redirect_id)) AS unique_hits, cut.channel_id, ph.lead_id FROM '.MAUTIC_TABLE_PREFIX.'channel_url_trackables cut INNER JOIN '.MAUTIC_TABLE_PREFIX."page_hits ph ON cut.redirect_id = ph.redirect_id AND cut.channel_id = ph.source_id WHERE (cut.channel = 'email' AND ph.source = 'email') AND (ph.lead_id in (:contacts)) GROUP BY cut.channel_id, ph.lead_id) sq ON es.email_id = sq.channel_id AND es.lead_id = sq.lead_id WHERE l.id in (:contacts) GROUP BY l.id";

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $expectedQuery,
                ['contacts' => [6, 8]],
                ['contacts' => 101]
            )
            ->willReturn($this->result);

        $this->result->method('fetchAllAssociative')
            ->willReturn([
                [
                    'lead_id'               => '6',
                    'sent_count'            => '12',
                    'read_count'            => '6',
                    'clicked_through_count' => '3',
                ],
                [
                    'lead_id'               => '8',
                    'sent_count'            => '13',
                    'read_count'            => '7',
                    'clicked_through_count' => '6',
                ],
            ]);

        $this->assertSame(
            [
                '6' => [
                    'sent_count'              => 12,
                    'read_count'              => 6,
                    'clicked_count'           => 3,
                    'open_rate'               => 0.5,
                    'click_through_rate'      => 0.25,
                    'click_through_open_rate' => 0.5,
                ],
                '8' => [
                    'sent_count'              => 13,
                    'read_count'              => 7,
                    'clicked_count'           => 6,
                    'open_rate'               => 0.5385,
                    'click_through_rate'      => 0.4615,
                    'click_through_open_rate' => 0.8571,
                ],
            ],
            $this->statRepository->getStatsSummaryForContacts([6, 8])
        );
    }

    public function testGetReadCount(): void
    {
        $expectedQuery = 'SELECT count(s.id) as count FROM test_email_stats s WHERE (s.email_id IN (1)) AND (is_read = :true) AND (s.date_read BETWEEN :dateFrom AND :dateTo)';
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $expectedQuery,
                [
                    'true'     => true,
                    'dateFrom' => '2023-01-01 00:00:00',
                    'dateTo'   => '2023-01-31 23:59:59',
                ]
            )
            ->willReturn($this->result);

        $this->result->method('fetchAllAssociative')
            ->willReturn([
                [
                    'count' => 1,
                ],
            ]);
        $query = new ChartQuery($this->connection, new \DateTime('2023-01-01'), new \DateTime('2023-01-31'));

        $this->assertSame(1, $this->statRepository->getReadCount(1, null, $query));
    }
}
