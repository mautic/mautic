<?php

declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomFieldColumnTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|ColumnSchemaHelper
     */
    private MockObject $columnSchemaHelper;

    /**
     * @var MockObject|SchemaDefinition
     */
    private MockObject $schemaDefinition;

    /**
     * @var MockObject|Logger
     */
    private MockObject $logger;

    /**
     * @var MockObject|LeadFieldSaver
     */
    private MockObject $leadFieldSaver;

    /**
     * @var MockObject|CustomFieldIndex
     */
    private MockObject $customFieldIndex;

    /**
     * @var MockObject|FieldColumnDispatcher
     */
    private MockObject $fieldColumnDispatcher;

    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    private CustomFieldColumn $customFieldColumn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $this->schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $this->logger                = $this->createMock(Logger::class);
        $this->leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $this->customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $this->fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);
        $this->translator            = $this->createMock(TranslatorInterface::class);
        $this->customFieldColumn     = new CustomFieldColumn(
            $this->columnSchemaHelper,
            $this->schemaDefinition,
            $this->logger,
            $this->leadFieldSaver,
            $this->customFieldIndex,
            $this->fieldColumnDispatcher,
            $this->translator
        );
    }

    public function testColumnExists(): void
    {
        $leadField = new LeadField();

        $this->columnSchemaHelper->expects($this->exactly(2))
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->exactly(2))
            ->method('checkColumnExists')
            ->willReturn(true);

        $this->fieldColumnDispatcher->expects($this->never())
            ->method('dispatchPreAddColumnEvent');

        $this->columnSchemaHelper->expects($this->never())
            ->method('addColumn');

        $this->customFieldColumn->createLeadColumn($leadField);
        $this->customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testAbortColumnCreation(): void
    {
        $this->columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $this->fieldColumnDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->willThrowException(new AbortColumnCreateException('Message'));

        $this->columnSchemaHelper->expects($this->never())
            ->method('addColumn');

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Message');

        $this->customFieldColumn->createLeadColumn(new LeadField());
    }

    public function testCustomFieldLimit(): void
    {
        $leadField = new LeadField();
        $leadField->setAlias('zip');
        $leadField->setType('text');

        $this->columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $this->schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn([]);

        $this->columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $dbalException = new class('message', 1118) extends \Exception implements \Doctrine\DBAL\Driver\Exception {
            public function getSQLState(): string
            {
                return 'some SQL state';
            }
        };

        $driverException = new \Doctrine\DBAL\Exception\DriverException($dbalException, null);

        $this->columnSchemaHelper->expects($this->once())
            ->method('executeChanges')
            ->willThrowException($driverException);

        $this->expectException(CustomFieldLimitException::class);
        $this->expectExceptionMessage('mautic.lead.field.max_column_error');

        $this->customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testNoErrorWithAddColumnIndex(): void
    {
        $leadField = new LeadField();
        $leadField->setAlias('zip');
        $leadField->setType('text');
        $leadField->setIsIndex(true);

        $this->columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $this->schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn(['type' => 'string']);

        $this->columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $this->columnSchemaHelper->expects($this->once())
            ->method('executeChanges');

        $this->leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, true);

        $this->customFieldIndex->expects($this->once())
            ->method('addIndexOnColumn')
            ->with($leadField);

        $this->customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testNoErrorWithAddColumnIndexForUniqueIdentifier(): void
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);
        $translator            = $this->createMock(TranslatorInterface::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher, $translator);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();
        $leadField->setAlias('text');
        $leadField->setIsUniqueIdentifier(true);

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

    public function testNoErrorWithAddColumnWithoutIndexOrUniqueIdentifier(): void
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);
        $translator            = $this->createMock(TranslatorInterface::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher, $translator);

        $columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($columnSchemaHelper);

        $columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $leadField = new LeadField();
        $leadField->setAlias('text');

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

        $customFieldIndex->expects($this->never())
            ->method('addIndexOnColumn');

        $customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testNoErrorWithUpdateAddColumnIndex(): void
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);
        $translator            = $this->createMock(TranslatorInterface::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher, $translator);

        $leadField = new LeadField();
        $leadField->setIsIndex(true);

        $customFieldIndex->expects($this->once())
            ->method('hasIndex')
            ->with($leadField)
            ->willReturn(false);

        $customFieldIndex->expects($this->once())
            ->method('addIndexOnColumn')
            ->with($leadField);

        $customFieldColumn->processUpdateLeadColumn($leadField);
    }

    public function testNoErrorWithUpdateRemoveColumnIndex(): void
    {
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $schemaDefinition      = $this->createMock(SchemaDefinition::class);
        $logger                = $this->createMock(Logger::class);
        $leadFieldSaver        = $this->createMock(LeadFieldSaver::class);
        $customFieldIndex      = $this->createMock(CustomFieldIndex::class);
        $fieldColumnDispatcher = $this->createMock(FieldColumnDispatcher::class);
        $translator            = $this->createMock(TranslatorInterface::class);

        $customFieldColumn = new CustomFieldColumn($columnSchemaHelper, $schemaDefinition, $logger, $leadFieldSaver, $customFieldIndex, $fieldColumnDispatcher, $translator);

        $leadField = new LeadField();
        $leadField->setIsIndex(false);

        $customFieldIndex->expects($this->once())
            ->method('hasIndex')
            ->with($leadField)
            ->willReturn(true);

        $customFieldIndex->expects($this->once())
            ->method('dropIndexOnColumn')
            ->with($leadField);

        $customFieldColumn->processUpdateLeadColumn($leadField);
    }

    public function testNoErrorNoColumnIndex(): void
    {
        $leadField = new LeadField();
        $leadField->setAlias('zip');
        $leadField->setType('text');

        $this->columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $this->schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn(['type' => 'date']);

        $this->columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $this->columnSchemaHelper->expects($this->once())
            ->method('executeChanges');

        $this->leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, true);

        $this->customFieldIndex->expects($this->never())
            ->method('addIndexOnColumn');

        $this->customFieldColumn->processCreateLeadColumn($leadField);
    }

    public function testUniqueIdentifierColumnCreation(): void
    {
        $leadField = new LeadField();
        // Creating the entity from a form will hydrate this with 0/1 instead of a true/false
        // Testing that the getter now appropriately returns a bool for the type hinted getSchemaDefinitionNonStatic
        $leadField->setIsUniqueIdentifier(1);
        $leadField->setAlias('zip');
        $leadField->setType('text');

        $this->columnSchemaHelper->expects($this->once())
            ->method('setName')
            ->willReturn($this->columnSchemaHelper);

        $this->columnSchemaHelper->expects($this->once())
            ->method('checkColumnExists')
            ->willReturn(false);

        $this->schemaDefinition->expects($this->once())
            ->method('getSchemaDefinitionNonStatic')
            ->willReturn(['type' => 'string']);

        $this->columnSchemaHelper->expects($this->once())
            ->method('addColumn');

        $this->columnSchemaHelper->expects($this->once())
            ->method('executeChanges');

        $this->leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, true);

        $this->customFieldIndex->expects($this->once())
            ->method('addIndexOnColumn')
            ->with($leadField);

        $this->customFieldColumn->processCreateLeadColumn($leadField);
    }
}
