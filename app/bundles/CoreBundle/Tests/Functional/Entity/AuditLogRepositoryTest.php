<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuditLogRepositoryTest extends MauticMysqlTestCase
{
    public function testLeadIpLogsRespectContactAndCreationDateWithOnlyFullGroupBy(): void
    {
        $contact = new Lead();
        $contact->setEmail('ip-log@example.invalid');
        $contact->setDateAdded(new \DateTime('2025-01-10 00:00:00'));
        $other = new Lead();
        $other->setEmail('other-ip-log@example.invalid');
        $this->em->persist($contact);
        $this->em->persist($other);
        $this->em->flush();

        $this->createIpLog($contact, '203.0.113.10', '2025-01-09 12:00:00');
        $first = $this->createIpLog($contact, '203.0.113.10', '2025-01-11 12:00:00');
        $this->createIpLog($contact, '203.0.113.10', '2025-01-12 12:00:00');
        $this->createIpLog($other, '203.0.113.10', '2025-01-13 12:00:00');
        $this->em->flush();

        $this->withOnlyFullGroupBy(function () use ($contact, $first): void {
            $repository = $this->em->getRepository(AuditLog::class);
            $rows = $repository->getLeadIpLogs($contact, ['paginated' => true]);
            self::assertSame(1, (int) $rows['total']);
            self::assertCount(1, $rows['results']);
            $row = $rows['results'][0];
            self::assertSame($contact->getId(), (int) $row['lead_id']);
            self::assertSame('203.0.113.10', $row['ip_address']);
            self::assertSame($first->getId(), (int) $row['id']);
            self::assertSame('2025-01-12 12:00:00', $row['date_added']->format('Y-m-d H:i:s'));
        });
    }

    public function testGlobalIpLogsKeepContactsSharingAnAddressSeparateWithOnlyFullGroupBy(): void
    {
        $first = new Lead();
        $first->setEmail('first-shared-ip@example.invalid');
        $second = new Lead();
        $second->setEmail('second-shared-ip@example.invalid');
        $this->em->persist($first);
        $this->em->persist($second);
        $this->em->flush();
        $this->createIpLog($first, '203.0.113.10', '2025-01-11 12:00:00');
        $this->createIpLog($first, '203.0.113.10', '2025-01-12 12:00:00');
        $this->createIpLog($second, '203.0.113.10', '2025-01-13 12:00:00');
        $this->createIpLog($second, '203.0.113.20', '2025-01-14 12:00:00');
        $this->em->flush();

        $this->withOnlyFullGroupBy(function () use ($first, $second): void {
            $repository = $this->em->getRepository(AuditLog::class);
            $rows = $repository->getLeadIpLogs(null, ['paginated' => true]);
            self::assertSame(3, (int) $rows['total']);
            self::assertCount(3, $rows['results']);
            $identities = array_map(static fn (array $row): string => $row['lead_id'].':'.$row['ip_address'], $rows['results']);
            self::assertEqualsCanonicalizing([
                $first->getId().':203.0.113.10',
                $second->getId().':203.0.113.10',
                $second->getId().':203.0.113.20',
            ], $identities);

            $page = $repository->getLeadIpLogs(null, ['paginated' => true, 'limit' => 1, 'order' => ['timestamp', 'DESC']]);
            self::assertSame(3, (int) $page['total']);
            self::assertCount(1, $page['results']);
            self::assertSame('203.0.113.20', $page['results'][0]['ip_address']);
        });
    }

    private function createIpLog(Lead $contact, string $address, string $date): AuditLog
    {
        $log = new AuditLog();
        $log->setBundle('lead');
        $log->setObject('lead');
        $log->setObjectId($contact->getId());
        $log->setAction('ipadded');
        $log->setIpAddress($address);
        $log->setDateAdded(new \DateTime($date));
        $log->setUserId(0);
        $log->setUserName('test');
        $this->em->persist($log);

        return $log;
    }

    /** @param callable(): void $assertions */
    private function withOnlyFullGroupBy(callable $assertions): void
    {
        $original = (string) $this->connection->fetchOne('SELECT @@SESSION.sql_mode');
        $strict = implode(',', array_unique([...array_filter(explode(',', $original)), 'ONLY_FULL_GROUP_BY']));
        $this->connection->executeStatement('SET SESSION sql_mode = ?', [$strict]);
        try {
            $assertions();
        } finally {
            $this->connection->executeStatement('SET SESSION sql_mode = ?', [$original]);
        }
    }

    /**
     * @param mixed[] $filters
     */
    #[DataProvider('dataForGetAuditLogsForLeads')]
    public function testGetAuditLogsForSingleLead(array $filters, int $expectedCount): void
    {
        /** @var LeadModel $contactModel */
        $contactModel = self::getContainer()->get(LeadModel::class);

        $contact = new Lead();
        $contact->setEmail('john@doe.com');

        $contactModel->saveEntity($contact);

        $contact->setFirstname('John')->setLastname('Doe');

        $contactModel->saveEntity($contact);

        $doNotContact = new DoNotContact();
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel('channelName');
        $doNotContact->setChannelId(1);

        $this->em->persist($doNotContact);
        $contact->addDoNotContactEntry($doNotContact);

        /** @var AuditLogRepository $alRepo */
        $alRepo = $this->em->getRepository(AuditLog::class);

        $this->assertCount($expectedCount, $alRepo->getAuditLogsForLeads([$contact->getId()], $filters));
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function dataForGetAuditLogsForLeads(): iterable
    {
        yield 'No filters' => [
            [],
            3,
        ];

        yield 'Filter: search' => [
            [
                'search' => 'john@doe.com',
            ],
            1,
        ];

        yield 'Filter: search for random text' => [
            [
                'search' => 'random text',
            ],
            0,
        ];

        yield 'Filter: includeEvents' => [
            [
                'includeEvents' => ['identified', 'create'],
            ],
            2,
        ];

        yield 'Filter: excludeEvents' => [
            [
                'excludeEvents' => ['identified', 'create'],
            ],
            1,
        ];
    }
}
