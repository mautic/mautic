<?php

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class CommonRepositoryUpsertTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        if ($this->isPostgresqlPlatform()) {
            $this->connection->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS idx_ip_address ON '.MAUTIC_TABLE_PREFIX.'ip_addresses (ip_address)');
        } else {
            $this->connection->executeStatement('ALTER TABLE '.MAUTIC_TABLE_PREFIX.'ip_addresses ADD UNIQUE INDEX idx_ip_address (ip_address)');
        }
    }

    protected function afterRollback(): void
    {
        if ($this->isPostgresqlPlatform()) {
            $this->connection->executeStatement('DROP INDEX idx_ip_address');
        } else {
            $this->connection->executeStatement('ALTER TABLE '.MAUTIC_TABLE_PREFIX.'ip_addresses DROP INDEX idx_ip_address');
        }
    }

    public function testUpsert(): void
    {
        // Insert twice, to get two insert IDs, and then insert the first one again to trigger update, and check insert ID
        /** @var IpAddressRepository $ipAddressRepository */
        $ipAddressRepository = $this->getContainer()->get(IpAddressRepository::class);
        $ipAddress1          = new IpAddress('10.10.10.10');
        $ipAddressRepository->upsert($ipAddress1);
        $this->assertNotEmpty($ipAddress1->getId());
        $ipAddress2 = new IpAddress('10.10.10.11');
        $ipAddressRepository->upsert($ipAddress2);
        $this->assertNotEmpty($ipAddress2->getId());
        $ipAddress3 = new IpAddress('10.10.10.10');
        $ipAddressRepository->upsert($ipAddress3);
        $this->assertEquals($ipAddress1->getId(), $ipAddress3->getId());
    }
}
