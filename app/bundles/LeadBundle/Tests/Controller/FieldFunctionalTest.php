<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class FieldFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testNewFieldVarcharFieldWith191Length(): void
    {
        $fieldModel = self::$container->get('mautic.lead.model.field');
        $field      = $this->createField('a');
        $fieldModel->saveEntity($field);

        $tablePrefix = self::$container->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->getSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertEquals(ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH, $columns[$field->getAlias()]->getLength());
    }

    public function testNewDateField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();

        $form['leadfield[label]']->setValue('Best Date Ever');
        $form['leadfield[type]']->setValue('date');

        $this->client->submit($form);

        $text = strip_tags($this->client->getResponse()->getContent());

        Assert::assertTrue($this->client->getResponse()->isOk(), $text);
        Assert::assertStringNotContainsString('New Custom Field', $text);
        Assert::assertStringNotContainsString('This form should not contain extra fields.', $text);
        Assert::assertStringContainsString('Edit Custom Field - Best Date Ever', $text);
    }

    private function createField(string $suffix): LeadField
    {
        $field = new LeadField();
        $field->setName("Field $suffix");
        $field->setAlias("field_$suffix");
        $field->setDateAdded(new \DateTime());
        $field->setDateAdded(new \DateTime());
        $field->setDateModified(new \DateTime());
        $field->setType('text');
        $field->setObject('lead');

        return $field;
    }
}
