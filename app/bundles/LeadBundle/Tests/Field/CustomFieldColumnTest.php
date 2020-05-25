<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\CustomFieldIndex;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Monolog\Logger;

class CustomFieldColumnTest extends \PHPUnit\Framework\TestCase
{
    public function testColumnExists()
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher);

        $columnSchemaHelper->expects($this->exactly(2))
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->exactly(2))
            ->method('checkColumnExists')
            ->willReturn(true);

        $leadField = new LeadField();

        $fieldColumnDispatcher->expects($this->never())
            ->method('dispatchPreAddColumnEvent');

        $columnSchemaHelper->expects($this->never())
            ->method('addColumn');

        $customFieldColumn->createLeadColumn($leadField);
        $customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testAbortColumnCreation()
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();

        $fieldColumnDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->willThrowException(new AbortColumnCreateException('Message'));

        $columnSchemaHelper->expects($this->never())
            ->method('addColumn');

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Message');

        $customFieldColumn->createLeadColumn($leadField);
    }

    public function testCustomFieldLimit()
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();

        $schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn([]);

        $columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $driverExceptionInterface = $this->createMock(\Doctrine\DBAL\Driver\DriverException::class);
        $driverExceptionInterface->expects($this->once())
            ->method('getErrorCode')
            ->willReturn(1118);

        $driverException = new \Doctrine\DBAL\Exception\DriverException('Message', $driverExceptionInterface);

        $columnSchemaHelper->expects($this->once())
            ->method('executeChanges')
            ->willThrowException($driverException);

        $this->expectException(CustomFieldLimitException::class);
        $this->expectExceptionMessage('mautic.lead.field.max_column_error');

        $customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testNoErrorWithAddColumnIndex()
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();

        $schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn(['type' => 'string']);

        $columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $columnSchemaHelper->expects($this->once())
            ->method('executeChanges');

        $leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, true);

        $customFieldIndex->expects($this->once())
            ->method('addIndexOnColumn')
            ->with($leadField);

        $customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testNoErrorNoColumnIndex()
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();

        $schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn(['type' => 'date']);

        $columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $columnSchemaHelper->expects($this->once())
            ->method('executeChanges');

        $leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, true);

        $customFieldIndex->expects($this->never())
            ->method('addIndexOnColumn');

        $customFieldColumn->processCreateLeadColumn($leadField);
    }
}
