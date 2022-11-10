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
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldColumnTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|ColumnSchemaHelper
     */
    private $columnSchemaHelper;

    /**
     * @var MockObject|SchemaDefinition
     */
    private $schemaDefinition;

    /**
     * @var MockObject|Logger
     */
    private $logger;

    /**
     * @var MockObject|LeadFieldSaver
     */
    private $leadFieldSaver;

    /**
     * @var MockObject|CustomFieldIndex
     */
    private $customFieldIndex;

    /**
     * @var MockObject|FieldColumnDispatcher
     */
    private $fieldColumnDispatcher;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomFieldColumn
     */
    private $customFieldColumn;

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

        $driverExceptionInterface = $this->createMock(\Doctrine\DBAL\Driver\DriverException::class);
        $driverExceptionInterface->expects($this->once())
            ->method('getErrorCode')
            ->willReturn(1118);

        $driverException = new \Doctrine\DBAL\Exception\DriverException('Message', $driverExceptionInterface);

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
