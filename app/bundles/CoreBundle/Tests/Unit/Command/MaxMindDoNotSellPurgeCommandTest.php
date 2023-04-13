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
use Symfony\Component\Console\Tester\CommandTester;

class MaxMindDoNotSellPurgeCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testCommandDryRun(): void
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn(['test1', 'test2']);

        $mockEntityManager = $this->buildMockEntityManager($resultMock);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $mockDoNotSellList);
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
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([]);
        $mockEntityManager = $this->buildMockEntityManager($resultMock);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $mockDoNotSellList);
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('No matches found', $output);
        $this->assertStringNotContainsString('contacts with IPs from Do Not Sell list', $output);
        $this->assertEquals(0, $result);
    }

    public function testPurge(): void
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([['id' => 1, 'ip_address' => '123.123.123.123']]);
        $mockEntityManager = $this->buildMockEntityManager($resultMock);
        $mockDoNotSellList = $this->createMock(MaxMindDoNotSellList::class);

        $command       = new MaxMindDoNotSellPurgeCommand($mockEntityManager, $mockDoNotSellList);
        $commandTester = new CommandTester($command);

        $result = $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Found 1 contacts with an IP from the Do Not Sell list', $output);
        $this->assertStringContainsString('Step 2: Purging data...', $output);
        $this->assertStringNotContainsString('No matches found', $output);
        $this->assertEquals(0, $result);
    }

    private function buildMockEntityManager(MockObject $dataToReturn): EntityManager
    {
        $mockStatement = $this->createMock(Statement::class);
        $mockStatement->method('executeQuery')->withAnyParameters()->willReturn($dataToReturn);

        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->method('prepare')->withAnyParameters()->willReturn($mockStatement);

        $ip = new IpAddress('123.123.123.123');
        $ip->setIpDetails(['city' => 'Boston', 'region' => 'MA', 'country' => 'United States', 'zipcode' => '02113']);

        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setCity('Boston');
        $lead->setState('MA');
        $lead->setCountry('United States');
        $lead->setZipcode('02113');

        $mockLeadRepository = $this->createMock(LeadRepository::class);
        $mockLeadRepository->method('findOneBy')->with(['id' => 1])->willReturn($lead);

        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->method('getConnection')->willReturn($mockConnection);
        $mockEntityManager->method('getRepository')->withAnyParameters()->willReturn($mockLeadRepository);

        return $mockEntityManager;
    }
}
