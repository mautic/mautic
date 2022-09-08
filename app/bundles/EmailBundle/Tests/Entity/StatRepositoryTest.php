<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\StatRepository;
use PHPUnit\Framework\MockObject\MockObject;

class StatRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $mockConnection;

    /**
     * @var EntityManager|MockObject
     */
    private $em;

    private StatRepository $statRepository;

    private QueryBuilder $qb;

    private QueryBuilder $subQb;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->mockConnection           = $this->createMock(Connection::class);
        $this->em                       = $this->createMock(EntityManager::class);
        $this->statRepository           = new StatRepository($this->em, $this->createMock(ClassMetadata::class));

        $this->qb    = new QueryBuilder($this->mockConnection);
        $this->subQb = new QueryBuilder($this->mockConnection);

        $this->mockConnection->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($this->qb, $this->subQb);

        $this->mockConnection->method('getExpressionBuilder')
            ->willReturnCallback(
                function () {
                    return new ExpressionBuilder($this->mockConnection);
                }
            );

        $this->mockConnection->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $this->em->method('getConnection')
            ->willReturn($this->mockConnection);
    }

    public function testGetStatsSummaryForContacts(): void
    {
        $expectedQuery = 'SELECT l.id AS `lead_id`, COUNT(es.id) AS `sent_count`, SUM(IF(es.is_read IS NULL, 0, es.is_read)) AS `read_count`, SUM(IF(sq.hits is NULL, 0, 1)) AS `clicked_through_count` FROM '.MAUTIC_TABLE_PREFIX.'email_stats es RIGHT JOIN '.MAUTIC_TABLE_PREFIX.'leads l ON es.lead_id=l.id LEFT JOIN (SELECT COUNT(ph.id) AS hits, COUNT(DISTINCT(ph.redirect_id)) AS unique_hits, cut.channel_id, ph.lead_id FROM '.MAUTIC_TABLE_PREFIX.'channel_url_trackables cut INNER JOIN '.MAUTIC_TABLE_PREFIX."page_hits ph ON cut.redirect_id = ph.redirect_id AND cut.channel_id = ph.source_id WHERE (cut.channel = 'email' AND ph.source = 'email') AND (ph.lead_id in (:contacts)) GROUP BY cut.channel_id, ph.lead_id) sq ON es.email_id = sq.channel_id AND es.lead_id = sq.lead_id WHERE l.id in (:contacts) GROUP BY l.id";
        $statement     = $this->createMock(Statement::class);
        $this->mockConnection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $expectedQuery,
                [':contacts' => [6, 8]],
                [':contacts' => 101]
            )
            ->willReturn($statement);

        $statement->method('fetchAll')
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

        $result = $this->statRepository->getStatsSummaryForContacts([6, 8]);

        $expectedResult = [
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
        ];

        $this->assertSame($expectedResult, $result);
    }
}
