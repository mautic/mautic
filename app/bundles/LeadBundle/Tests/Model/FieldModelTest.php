<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;

class FieldModelTest extends MauticMysqlTestCase
{
    public function testSingleContactFieldIsCreatedAndDeleted()
    {
        $fieldModel = $this->container->get('mautic.lead.model.field');

        $field = new LeadField();
        $field->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');

        $fieldModel->saveEntity($field);
        $fieldModel->deleteEntity($field);

        $this->assertCount(0, $this->getColumns('leads', $field->getAlias()));
    }

    public function testSingleCompanyFieldIsCreatedAndDeleted()
    {
        $fieldModel = $this->container->get('mautic.lead.model.field');

        $field = new LeadField();
        $field->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('company');

        $fieldModel->saveEntity($field);
        $fieldModel->deleteEntity($field);

        $this->assertCount(0, $this->getColumns('companies', $field->getAlias()));
    }

    public function testMultipleFieldsAreCreatedAndDeleted()
    {
        $fieldModel = $this->container->get('mautic.lead.model.field');

        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');

        $leadField2 = new LeadField();
        $leadField2->setName('Test Field')
            ->setAlias('test_field2')
            ->setType('string')
            ->setObject('lead');

        $companyField = new LeadField();
        $companyField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('company');

        $companyField2 = new LeadField();
        $companyField2->setName('Test Field')
            ->setAlias('test_field2')
            ->setType('string')
            ->setObject('company');

        $fieldModel->saveEntities([$leadField, $leadField2, $companyField, $companyField2]);

        $this->assertCount(1, $this->getColumns('leads', $leadField->getAlias()));
        $this->assertCount(1, $this->getColumns('leads', $leadField2->getAlias()));
        $this->assertCount(1, $this->getColumns('companies', $companyField->getAlias()));
        $this->assertCount(1, $this->getColumns('companies', $companyField2->getAlias()));

        $fieldModel->deleteEntities([$leadField->getId(), $leadField2->getId(), $companyField->getId(), $companyField2->getId()]);

        $this->assertCount(0, $this->getColumns('leads', $leadField->getAlias()));
        $this->assertCount(0, $this->getColumns('leads', $leadField2->getAlias()));
        $this->assertCount(0, $this->getColumns('companies', $companyField->getAlias()));
        $this->assertCount(0, $this->getColumns('companies', $companyField2->getAlias()));
    }

    /**
     * @param $table
     * @param $column
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getColumns($table, $column)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $stmt       = $connection->executeQuery(
            "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$connection->getDatabase()}' AND TABLE_NAME = '".MAUTIC_TABLE_PREFIX
            ."$table' AND COLUMN_NAME = '$column'"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
