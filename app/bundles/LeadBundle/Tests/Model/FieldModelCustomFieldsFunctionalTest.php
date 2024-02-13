<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

final class FieldModelCustomFieldsFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testGetLeadFields(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $fields     = $fieldModel->getLeadFields();
        $expected   = count(FieldModel::$coreFields);
        $this->assertGreaterThanOrEqual($expected, count($fields));
    }

    public function testLeadFieldCustomFields(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');

        $fields = $fieldModel->getLeadFieldCustomFields();
        $this->assertEmpty($fields, 'There are no Custom Fields.');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');
        $fieldModel->saveEntity($leadField);

        $fields = $fieldModel->getLeadFieldCustomFields();
        $this->assertEquals(1, count($fields));
    }

    public function testGetLeadCustomFieldsSchemaDetails(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');
        $fieldModel->saveEntity($leadField);

        $schemas = $fieldModel->getLeadFieldCustomFieldSchemaDetails();
        $this->assertEquals(1, count($schemas));
    }
}
