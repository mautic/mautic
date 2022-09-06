<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;

class FieldFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var string
     */
    private $alias;

    public function testNewFieldVarcharFieldWith191Length(): void
    {
        $fieldModel = self::$container->get('mautic.lead.model.field');
        $field      = $this->createField();
        $fieldModel->saveEntity($field);

        $tablePrefix = self::$container->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->getSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertEquals(191, $columns[$this->alias]->getLength());
    }

    private function createField(string $suffix = 'a'): LeadField
    {
        $field = new LeadField();
        $field->setName("Field $suffix");
        $this->alias = "field_$suffix";
        $field->setAlias($this->alias);
        $field->setDateAdded(new \DateTime());
        $field->setDateAdded(new \DateTime());
        $field->setDateModified(new \DateTime());
        $field->setType('text');
        $field->setObject('lead');

        return $field;
    }
}
