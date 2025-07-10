<?php

namespace Mautic\CoreBundle\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Command\MaxMindDoNotSellPurgeCommand;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MaxMindDoNotSellPurgeCommandTest extends TestCase
{
    private MockObject $mockLeadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $ip = new IpAddress('123.123.123.123');
        $ip->setIpDetails(['city' => 'Boston', 'region' => 'MA', 'country' => 'United States', 'zipcode' => '02113']);

        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setCity('Boston');
        $lead->setState('MA');
        $lead->setCountry('United States');
        $lead->setZipcode('02113');

        $this->mockLeadRepository = $this->createMock(LeadRepository::class);
        $this->mockLeadRepository->method('findOneBy')->with(['id' => 1])->willReturn($lead);
    }

    public function testCommandDryRun(): void
    {
        $mockEntityManager = $this->buildMockEntityManager(['test1', 'test2']);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $this->mockLeadRepository, $mockDoNotSellList);
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute(['--dry-run' => true]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Dry run; skipping purge', $output);
        $this->assertStringNotContainsString('No matches found', $output);
        $this->assertStringNotContainsString('Step 2: Purging data...', $output);
        $this->assertEquals(0, $result);
    }

    public function testNoContactsFound(): void
    {
        $mockEntityManager = $this->buildMockEntityManager([]);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $this->mockLeadRepository, $mockDoNotSellList);
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('No matches found', $output);
        $this->assertStringNotContainsString('contacts with IPs from Do Not Sell list', $output);
        $this->assertEquals(0, $result);
    }

    public function testPurge(): void
    {
        $mockEntityManager = $this->buildMockEntityManager([['id' => 1, 'ip_address' => '123.123.123.123']]);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $this->mockLeadRepository, $mockDoNotSellList);
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Found 1 contacts with an IP from the Do Not Sell list', $output);
        $this->assertStringContainsString('Step 2: Purging data...', $output);
        $this->assertStringNotContainsString('No matches found', $output);
        $this->assertEquals(0, $result);
    }

    private function buildMockEntityManager(array $dataToReturn): EntityManager
    {
        $mockStatement = $this->createMock(Statement::class);
        $resultMock    = $this->createMock(Result::class);
        $mockStatement->method('executeQuery')->withAnyParameters()->willReturn($resultMock);
        $resultMock->method('fetchAllAssociative')->withAnyParameters()->willReturn($dataToReturn);

        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->method('prepare')->withAnyParameters()->willReturn($mockStatement);

        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->method('getConnection')->willReturn($mockConnection);

        return $mockEntityManager;
    }
}
