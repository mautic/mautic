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
        $expectedQuery = 'SELECT l.id AS `lead_id`, COUNT(es.id) AS `sent_count`, SUM(IF(es.is_read IS NULL, 0, es.is_read)) AS `read_count`, SUM(IF(sq.hits is NULL, 0, 1)) AS `clicked_through_count` FROM '.MAUTIC_TABLE_PREFIX.'email_stats es RIGHT JOIN '.MAUTIC_TABLE_PREFIX.'leads l ON es.lead_id=l.id LEFT JOIN (SELECT COUNT(ph.id) AS hits, COUNT(DISTINCT(ph.redirect_id)) AS unique_hits, cut.channel_id, ph.lead_id FROM '.MAUTIC_TABLE_PREFIX.'channel_url_trackables cut INNER JOIN '.MAUTIC_TABLE_PREFIX."page_hits ph ON cut.redirect_id = ph.redirect_id AND cut.channel_id = ph.source_id WHERE (cut.channel = 'email' AND ph.source = 'email') AND (ph.lead_id in (:contacts)) GROUP BY cut.channel_id, ph.lead_id) sq ON es.email_id = sq.channel_id AND es.lead_id = sq.lead_id WHERE l.id in (:contacts) GROUP BY l.id";

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

    public function testGetSentEmailToContactDataBuildsOnlyFullGroupByCompliantQuery(): void
    {
        $expectedRow = [
            'id'            => '101',
            'lead_id'       => '7',
            'email_address' => 'contact@example.test',
            'is_read'       => '1',
            'email_id'      => '22',
            'date_sent'     => '2026-03-10 08:00:00',
            'date_read'     => '2026-03-10 08:30:00',
            'email_name'    => 'March Newsletter',
            'link_hits'     => '3',
            'company_id'    => '11',
            'company_name'  => 'ACME',
            'campaign_id'   => '5',
            'campaign_name' => 'Spring Campaign',
            'segment_id'    => '13',
            'segment_name'  => 'VIP Contacts',
        ];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->willReturnCallback(function (string $sql, array $params = [], array $types = []) {
                self::assertStringContainsString('SELECT s.id AS id, s.lead_id AS lead_id, s.email_address AS email_address, s.is_read AS is_read, s.email_id AS email_id, s.date_sent AS date_sent, s.date_read AS date_read, e.name AS email_name, c.id AS company_id, c.companyname AS company_name, campaign.id AS campaign_id, campaign.name AS campaign_name, ll.id AS segment_id, ll.name AS segment_name, COUNT(ph.id) AS link_hits', $sql);
                self::assertStringContainsString('LEFT JOIN test_companies_leads cl ON s.lead_id = cl.lead_id AND cl.is_primary = 1', $sql);
                self::assertStringContainsString('GROUP BY s.id, s.lead_id, s.email_address, s.is_read, s.email_id, s.date_sent, s.date_read, e.name, c.id, c.companyname, campaign.id, campaign.name, ll.id, ll.name', $sql);
                self::assertStringNotContainsString('GROUP BY s.id AS', $sql);
                self::assertSame('2026-03-01 00:00:00', $params['dateFrom']);
                self::assertSame('2026-03-31 23:59:59', $params['dateTo']);

                return $this->result;
            });

        $this->result->method('fetchAllAssociative')
            ->willReturn([$expectedRow]);

        $this->assertSame(
            [$expectedRow],
            $this->statRepository->getSentEmailToContactData(
                10,
                new \DateTime('2026-03-01 00:00:00'),
                new \DateTime('2026-03-31 23:59:59')
            )
        );
    }
}
