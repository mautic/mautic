<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Helper;

use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

final class IndexHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIndexCountAndColumns(): void
    {
        $tableName = 'table_name';

        $columnNames = [
            'id', '0', '1', '1', '2', '2',
        ];

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

        $ishMock->expects($this->once())
            ->method('getTableIndexes')
            ->with($tableName)
            ->willReturn($indexes);

        $helper = new IndexHelper($emMock, $ishMock);

        $this->assertSame($expectedColumnNames, $helper->getIndexedColumnNames());
        $this->assertSame($expectedCount, $helper->getIndexCount());
    }
}
