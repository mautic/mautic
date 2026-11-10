<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\Migrations\Version20261109120000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CompanyFilterMigrationTest extends TestCase
{
    public function testExistingCompanyFiltersKeepTheirHistoricalScopeMarker(): void
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
            ->with('SELECT id, filters FROM test_lead_lists WHERE id > :last_id AND filters LIKE :filter ORDER BY id ASC LIMIT 100', ['last_id' => 0, 'filter' => '%s:6:"object";s:7:"company"%'])
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
                'object'   => 'company',
                'field'    => 'companycountry',
                'operator' => '=',
                'filter'   => 'Germany',
                ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE => ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE_VALUE,
            ]], 12],
            [$parameters['filters'] ? Serializer::decode($parameters['filters']) : null, $parameters['id']]
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

    public function testMigrationPreservesMultipleCompanyFiltersAndOtherFilters(): void
    {
        $filters = [
            ['object' => 'company', 'field' => 'companycountry'],
            ['object' => 'company', 'field' => 'companyname'],
            ['object' => 'lead', 'field' => 'country'],
        ];
        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 14, 'filters' => serialize($filters)],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeQuery')->willReturn($result);

        $migration = new Version20261109120000($connection, new NullLogger());
        $migration->setPrefix('test_');
        $migration->up(new Schema());

        $migrated = Serializer::decode($migration->getSql()[0]->getParameters()['filters']);
        $this->assertSame(ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE_VALUE, $migrated[0][ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE]);
        $this->assertSame(ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE_VALUE, $migrated[1][ContactSegmentFilterCrate::LEGACY_COMPANY_FILTER_SCOPE]);
        $this->assertSame($filters[2], $migrated[2]);
    }

    public function testMalformedFiltersAreSkippedWithoutAbortingTheMigration(): void
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchAllAssociative')->willReturn([
            ['id' => 15, 'filters' => 'not-serialized'],
        ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeQuery')->willReturn($result);

        $migration = new Version20261109120000($connection, new NullLogger());
        $migration->setPrefix('test_');
        $migration->up(new Schema());

        $this->assertSame([], $migration->getSql());
    }
}
