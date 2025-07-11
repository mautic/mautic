<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Helper;

use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

class ColumnSchemaHelperFunctionalTest extends MauticMysqlTestCase
{
    private LeadField $field;
    private ColumnSchemaHelper $schemaHelper;

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field        = $this->createCustomField();
        $this->schemaHelper = $this->getContainer()->get('mautic.schema.helper.column');
    }

    public function testUpdateColumnSchemaLengthSuccessfully(): void
    {
        $newLength = 100;
        $this->schemaHelper->updateColumnLength($this->field->getAlias(), $newLength);

        $column = $this->schemaHelper->getColumns()[$this->field->getAlias()];
        \assert($column instanceof Column);

        $this->assertEquals($newLength, $column->getLength(), 'Column length updated.');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataUpdateColumnExceptionCheck')]
    public function testUpdateColumnLengthThrowsException(string $column, int $len, string $message): void
    {
        $this->expectExceptionMessageMatches($message);
        $this->schemaHelper->updateColumnLength($column, $len);
    }

    /**
     * @return mixed[]
     */
    public static function dataUpdateColumnExceptionCheck(): iterable
    {
        // name, length, exception msg.
        yield 'Column name missing.' => ['', 10, '/The column name is should not be empty\/missing./'];
        yield 'Column name does not exist.' => ['does_not_exists', 10, '/There is no column with name "does_not_exists" on table/'];
        yield 'Out of range, when length is 0.' => ['custom_field_test', 0, '/Column length should be between 1 and 191./'];
        yield 'Out of range, when length greater than 191.' => ['custom_field_test', 195, '/Column length should be between 1 and 191./'];
    }

    private function createCustomField(): LeadField
    {
        $field = new LeadField();
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setLabel('Test field');
        $field->setAlias('custom_field_test');
        $field->setCharLengthLimit(64);

        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);
        $fieldModel->getRepository()->detachEntity($field);

        return $field;
    }
}
