<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

class IndexHelperTest extends \PHPUnit\Framework\TestCase
{
    public const COLUMN_NAME_KEY = 'Column_name';

    public function testGetIndexCountAndColumns(): void
    {
        $tableName = 'table_name';

        $columnNames = [
            'id', '0', '1', '1', '2', '2',
        ];

        // Create individual single-column indexes to match the expected behavior
        // (6 indexes, each with one "column" – this preserves the original test intent
        // of having duplicate entries in the flattened list while indexCount == column count)
        $indexes = [];
        foreach ($columnNames as $columnName) {
            $indexMock = $this->createMock(Index::class);
            $indexMock->method('getColumns')
                ->willReturn([$columnName]);
            $indexes[] = $indexMock;
        }

        $expectedColumnNames = $columnNames;
        $expectedCount       = count($expectedColumnNames);

        $emMock  = $this->createMock(EntityManager::class);
        $ishMock = $this->createMock(IndexSchemaHelper::class);

        $mdMock = $this->createMock(ClassMetadata::class);
        $mdMock->expects($this->once())
            ->method('getTableName')
            ->willReturn($tableName);

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with(Lead::class)
            ->willReturn($mdMock);

        // Mock the platform-agnostic IndexSchemaHelper
        $ishMock->expects($this->once())
            ->method('getTableIndexes')
            ->with($tableName)
            ->willReturn($indexes);

        $helper = new IndexHelper($emMock, $ishMock);

        $this->assertSame($expectedColumnNames, $helper->getIndexedColumnNames());
        $this->assertSame($expectedCount, $helper->getIndexCount());
    }
}
