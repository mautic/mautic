<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Timeline;

use Doctrine\DBAL\Schema\Schema;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\Migrations\Version20251128024511;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

final class TimelinePrecisionTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testUpgradePreservesExistingDatesAndStoresMilliseconds(): void
    {
        $connection = $this->em->getConnection();
        $prefix     = MAUTIC_TABLE_PREFIX.'precision_';
        $columns    = ['asset_downloads' => 'date_download', 'audit_log' => 'date_added', 'dynamic_content_stats' => 'date_sent'];
        $migration  = new Version20251128024511($connection, new NullLogger());
        $migration->setPrefix($prefix);
        $migration->up(new Schema());

        foreach ($columns as $table => $column) {
            $table = $prefix.$table;
            $connection->executeStatement("CREATE TEMPORARY TABLE $table (id INT PRIMARY KEY, $column DATETIME NOT NULL)");
            try {
                $connection->insert($table, ['id' => 1, $column => '2025-11-28 12:00:00']);
                $applied = false;
                foreach ($migration->getSql() as $query) {
                    if (str_starts_with($query->getStatement(), "ALTER TABLE $table ")) {
                        $connection->executeStatement($query->getStatement());
                        $applied = true;
                    }
                }
                $this->assertTrue($applied, "Missing migration for $table");
                $connection->insert($table, ['id' => 2, $column => '2025-11-28 12:00:00.200']);
                $this->assertSame(
                    ['2025-11-28 12:00:00.000', '2025-11-28 12:00:00.200'],
                    $connection->executeQuery("SELECT $column FROM $table ORDER BY id")->fetchFirstColumn()
                );
                $definition = $connection->executeQuery("SHOW COLUMNS FROM $table LIKE '$column'")->fetchAssociative();
                $this->assertSame('NO', $definition['Null']);
            } finally {
                $connection->executeStatement("DROP TEMPORARY TABLE $table");
            }
        }
    }

    /**
     * @param string[] $expectedTypes
     * @param string[] $expectedTimes
     */
    #[DataProvider('provideOrder')]
    public function testMixedTimelinePreservesMilliseconds(string $direction, array $expectedTypes, array $expectedTimes): void
    {
        $contact = new Lead();
        $contact->setEmail('precision@example.com');
        $contact->setDateAdded(new \DateTime('2025-11-27', new \DateTimeZone('UTC')));
        $ip = new IpAddress('192.0.2.123');
        $contact->addIpAddress($ip);
        $this->em->persist($ip);
        $this->em->persist($contact);
        $this->em->flush();

        $asset = new Asset();
        $asset->setTitle('Zulu asset');
        $asset->setAlias('precision-asset');
        $download = new Download();
        $download->setCode(200);
        $download->setTrackingId('precision-download');
        $download->setAsset($asset);
        $download->setLead($contact);
        $download->setDateDownload(new \DateTime('2025-11-28 12:00:00.200', new \DateTimeZone('UTC')));

        $content = new DynamicContent();
        $content->setName('Alpha content');
        $stat = new Stat();
        $stat->setDynamicContent($content);
        $stat->setLead($contact);
        $stat->setDateSent(new \DateTime('2025-11-28 12:00:00.100', new \DateTimeZone('UTC')));

        $audit = new AuditLog();
        $audit->setBundle('lead');
        $audit->setObject('lead');
        $audit->setObjectId($contact->getId());
        $audit->setAction('ipadded');
        $audit->setUserId(0);
        $audit->setUserName('system');
        $audit->setIpAddress($ip->getIpAddress());
        $audit->setDateAdded(new \DateTime('2025-11-28 12:00:00.300', new \DateTimeZone('UTC')));

        foreach ([$asset, $download, $content, $stat, $audit] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        $contactId = $contact->getId();
        $this->em->clear();

        $events = self::getContainer()->get(LeadModel::class)->getEngagements(
            $this->em->find(Lead::class, $contactId),
            ['search' => '', 'includeEvents' => ['asset.download', 'dynamic.content.sent', 'lead.ipadded'], 'excludeEvents' => []],
            ['timestamp', $direction]
        )['events'];

        $this->assertSame($expectedTypes, array_column($events, 'event'));
        $times = [];
        foreach ($events as $event) {
            $this->assertInstanceOf(\DateTime::class, $event['timestamp']);
            $timestamp = clone $event['timestamp'];
            $timestamp->setTimezone(new \DateTimeZone('UTC'));
            $times[] = $timestamp->format('Y-m-d H:i:s.u');
        }
        $this->assertSame($expectedTimes, $times);

        $expectedDisplayTime = self::getContainer()->get(DateHelper::class)->toTime(
            new \DateTime('2025-11-28 12:00:00', new \DateTimeZone('UTC'))
        );
        $crawler = $this->client->request('POST', '/s/contacts/timeline/'.$contactId, [
            'search'        => '',
            'includeEvents' => ['asset.download', 'dynamic.content.sent', 'lead.ipadded'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(
            array_fill(0, 3, $expectedDisplayTime),
            $crawler->filter('#contact-timeline td.timeline-timestamp')->each(static fn ($node): string => $node->text())
        );
    }

    /**
     * @return iterable<string, array{string, string[], string[]}>
     */
    public static function provideOrder(): iterable
    {
        $types = ['dynamic.content.sent', 'asset.download', 'lead.ipadded'];
        $times = ['2025-11-28 12:00:00.100000', '2025-11-28 12:00:00.200000', '2025-11-28 12:00:00.300000'];

        yield 'ASC' => ['ASC', $types, $times];
        yield 'DESC' => ['DESC', array_reverse($types), array_reverse($times)];
    }
}
