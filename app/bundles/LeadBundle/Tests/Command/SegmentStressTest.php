<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\Console\Command\Command;

class SegmentStressTest extends MauticMysqlTestCase
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
        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode(), $commandTester->getDisplay());
    }

    private function saveContacts(): void
    {
        for ($i = 0; $i <= 10000; ++$i) {
            $this->createLead('fn'.$i, 'ln'.$i);

            if (0 === $i % 100) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
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
