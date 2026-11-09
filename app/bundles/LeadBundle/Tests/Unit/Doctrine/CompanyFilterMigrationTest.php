<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Mautic\Migrations\Version20261109120000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CompanyFilterMigrationTest extends TestCase
{
    public function testExistingCompanyFiltersAreMigratedToAllAssociatedCompanies(): void
    {
        $oldFilters = [
            [
                'glue'     => 'and',
                'object'   => 'company',
                'field'    => 'companycountry',
                'operator' => '=',
                'filter'   => 'Germany',
            ],
        ];
        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 12, 'filters' => serialize($oldFilters)],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT id, filters FROM test_lead_lists WHERE filters LIKE :filter', ['filter' => '%company%'])
            ->willReturn($result);

        $migration = new Version20261109120000($connection, new NullLogger());
        $migration->setPrefix('test_');
        $migration->up(new Schema());

        $sql = $migration->getSql();
        $this->assertCount(1, $sql);
        $this->assertSame(
            'UPDATE test_lead_lists SET filters = :filters WHERE id = :id',
            $sql[0]->getStatement()
        );
        $parameters = $sql[0]->getParameters();
        $this->assertSame(
            [[[
                'glue'     => 'and',
                'object'   => 'company_all',
                'field'    => 'companycountry',
                'operator' => '=',
                'filter'   => 'Germany',
            ]], 12],
            [$parameters['filters'] ? unserialize($parameters['filters']) : null, $parameters['id']]
        );
    }

    public function testExistingCompanyAllAndOtherFiltersAreNotChanged(): void
    {
        $filters = [
            ['object' => 'company_all', 'field' => 'companycountry'],
            ['object' => 'lead', 'field' => 'country'],
        ];
        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 13, 'filters' => serialize($filters)],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeQuery')->willReturn($result);

        $migration = new Version20261109120000($connection, new NullLogger());
        $migration->setPrefix('test_');
        $migration->up(new Schema());

        $this->assertSame([], $migration->getSql());
    }
}
