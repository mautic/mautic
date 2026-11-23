<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\Console\Command\Command;

final class SegmentStressTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private string $initialMemoryLimit;

    protected function setUp(): void
    {
        parent::setUp();

        $memoryLimit = ini_get('memory_limit');

        if (!is_string($memoryLimit)) {
            self::fail('Memory limit should be a string.');
        }

        $this->initialMemoryLimit = $memoryLimit;
        $currentMemoryUsage       = memory_get_usage(true);
        $currentMemoryUsageMb     = (int) ceil($currentMemoryUsage / 1024 / 1024);
        $currentMemoryUsageMb += 30; // Should be enough.

        ini_set('memory_limit', $currentMemoryUsageMb.'M');
    }

    protected function beforeTearDown(): void
    {
        parent::beforeTearDown();

        ini_set('memory_limit', $this->initialMemoryLimit);
    }

    public function testSegmentStressTest(): void
    {
        $this->saveContacts();
        $segmentA   = $this->saveSegment();
        $segmentAId = $segmentA->getId();
        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['-i' => $segmentAId]);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode(), $commandTester->getDisplay());
    }

    private function saveContacts(): void
    {
        // bulk insert, the ORM is too slow for this amount of contacts
        $connection = $this->em->getConnection();
        $dateAdded  = new \DateTime()->format('Y-m-d H:i:s');

        foreach (array_chunk(range(0, 10000), 1000) as $chunk) {
            $rows   = [];
            $params = [];
            foreach ($chunk as $i) {
                $rows[] = '(?, ?, 1, 0, ?)';
                $params[] = 'fn'.$i;
                $params[] = 'ln'.$i;
                $params[] = $dateAdded;
            }

            $connection->executeStatement(
                'INSERT INTO '.MAUTIC_TABLE_PREFIX.'leads (firstname, lastname, is_published, points, date_added) VALUES '.implode(', ', $rows),
                $params
            );
        }
    }

    private function saveSegment(): LeadList
    {
        $filters = [
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'firstname',
                'type'       => 'text',
                'operator'   => 'startsWith',
                'properties' => ['filter' => 'fn'],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'or',
                'field'      => 'lastname',
                'type'       => 'text',
                'operator'   => 'startsWith',
                'properties' => ['filter' => 'ln'],
            ],
        ];

        $segment = $this->createSegment('segment-a', $filters);
        $this->em->flush();

        return $segment;
    }
}
