<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Field\InputFormField;
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
        $columns     = $this->connection->createSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertEquals(ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH, $columns[$field->getAlias()]->getLength());
    }

    public function testNewMultiSelectField(): void
    {
        $fieldModel = self::$container->get('mautic.lead.model.field');
        $field      = $this->createField('s', 'select', ['properties' => ['list' => ['choice_a' => 'Choice A']]]);
        $fieldModel->saveEntity($field);

        $tablePrefix = self::$container->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->createSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertArrayHasKey('field_s', $columns);
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

    public function testNewSelectField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $domDocument = $crawler->getNode(0)->ownerDocument;
        $inputLabel  = $domDocument->createElement('input');
        $inputLabel->setAttribute('type', 'text');

        $inputLabel->setAttribute('name', 'leadfield[properties][list][0][label]');
        $inputValue  = $domDocument->createElement('input');
        $inputValue->setAttribute('type', 'text');
        $inputValue->setAttribute('name', 'leadfield[properties][list][0][value]');

        $form        = $crawler->selectButton('Save')->form();
        $form->set(new InputFormField($inputLabel));
        $form->set(new InputFormField($inputValue));

        $form['leadfield[label]']->setValue('Test select field');
        $form['leadfield[type]']->setValue('select');
        $form['leadfield[properties][list][0][label]']->setValue('Label 1');
        $form['leadfield[properties][list][0][value]']->setValue('Value 1');

        $this->client->submit($form);

        $text = strip_tags($this->client->getResponse()->getContent());

        Assert::assertTrue($this->client->getResponse()->isOk(), $text);
        Assert::assertStringNotContainsString('New Custom Field', $text);
        Assert::assertStringNotContainsString('This form should not contain extra fields.', $text);
        Assert::assertStringContainsString('Edit Custom Field - Test select field', $text);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createField(string $suffix, string $type = 'text', array $parameters = []): LeadField
    {
        $field = new LeadField();
        $field->setName("Field $suffix");
        $field->setAlias("field_$suffix");
        $field->setDateAdded(new \DateTime());
        $field->setDateAdded(new \DateTime());
        $field->setDateModified(new \DateTime());
        $field->setType($type);
        $field->setObject('lead');
        isset($parameters['properties']) && $field->setProperties($parameters['properties']);

        return $field;
    }
}
