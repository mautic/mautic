<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

class IndexHelperTest extends \PHPUnit\Framework\TestCase
{
    public const COLUMN_NAME_KEY = 'Column_name';

    public function testGetIndexCountAndColumns(): void
    {
        $tableName   = 'table_name';
        $sql         = "SHOW INDEXES FROM `$tableName`";
        $columnNames = [
            'id', '0', '1', '1', '2', '2',
        ];
        foreach ($columnNames as $columnName) {
            $sqlResult[][self::COLUMN_NAME_KEY] = $columnName;
        }
        $expectedColumnNames = array_map(
            function ($column) {
                return $column[self::COLUMN_NAME_KEY];
            },
            $sqlResult
        );

        $expectedCount = count($expectedColumnNames);

        $emMock = $this->createMock(EntityManager::class);
        $helper = new IndexHelper($emMock);

        $mdMock = $this->createMock(ClassMetadata::class);

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with(Lead::class)
            ->willReturn($mdMock);

        $mdMock->expects($this->once())
            ->method('getTableName')
            ->willReturn($tableName);

        $connMock = $this->createMock(Connection::class);

        $emMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connMock);

        $stmtMock = $this->createMock(Statement::class);
        $result   = $this->createMock(Result::class);
        $connMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($sqlResult);

        $this->assertEquals($expectedColumnNames, $helper->getIndexedColumnNames());
        $this->assertEquals($expectedCount, $helper->getIndexCount());
    }
}
