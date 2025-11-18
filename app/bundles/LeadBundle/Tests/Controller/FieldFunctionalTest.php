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

    /**
     * @dataProvider provideFieldLength
     */
    public function testNewFieldVarcharFieldLength(int $expectedLength, ?int $inputLength = null): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $field      = $this->createField('a', 'text', [], $inputLength);
        $fieldModel->saveEntity($field);

        $tablePrefix = static::getContainer()->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->createSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertEquals($expectedLength, $columns[$field->getAlias()]->getLength());
    }

    public function testNewMultiSelectField(): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $field      = $this->createField('s', 'select', ['properties' => ['list' => ['choice_a' => 'Choice A']]]);
        $fieldModel->saveEntity($field);

        $tablePrefix = static::getContainer()->getParameter('mautic.db_table_prefix');
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
     * @param array<string, string> $properties
     *
     * @dataProvider dataForCreatingNewBooleanField
     */
    public function testCreatingNewBooleanField(array $properties, string $expectedMessage): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $domDocument = $crawler->getNode(0)->ownerDocument;
        $yesLabel    = $domDocument->createElement('input');
        $yesLabel->setAttribute('type', 'text');
        $yesLabel->setAttribute('name', 'leadfield[properties][yes]');

        $noLabel  = $domDocument->createElement('input');
        $noLabel->setAttribute('type', 'text');
        $noLabel->setAttribute('name', 'leadfield[properties][no]');

        $form = $crawler->selectButton('Save')->form();
        $form->set(new InputFormField($yesLabel));
        $form->set(new InputFormField($noLabel));

        $form['leadfield[label]']->setValue('Request a meeting');
        $form['leadfield[type]']->setValue('boolean');
        $form['leadfield[object]']->setValue('lead');
        $form['leadfield[group]']->setValue('core');

        $form['leadfield[properties][yes]']->setValue($properties['yes'] ?? '');
        $form['leadfield[properties][no]']->setValue($properties['no'] ?? '');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $text = strip_tags($this->client->getResponse()->getContent());
        Assert::assertStringNotContainsString($expectedMessage, $text);
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public function dataForCreatingNewBooleanField(): iterable
    {
        yield 'No properties' => [
            [],
            'A \'positive\' label is required.',
        ];

        yield 'Only Yes' => [
            [
                'yes' => 'Yes',
            ],
            'A \'negative\' label is required.',
        ];

        yield 'Only No' => [
            [
                'no' => 'No',
            ],
            'A \'positive\' label is required.',
        ];
    }

    public function testCheckDefaultBooleanFieldSetting(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Check if the radio button with value 0 is checked and value 1 is not
        Assert::assertNotNull(
            $crawler->filter('#leadfield_default_template_boolean_0')->attr('checked')
        );
        Assert::assertNull(
            $crawler->filter('#leadfield_default_template_boolean_1')->attr('checked')
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createField(string $suffix, string $type = 'text', array $parameters = [], ?int $charLength = null): LeadField
    {
        $field = new LeadField();
        $field->setName("Field $suffix");
        $field->setAlias("field_$suffix");
        $field->setDateAdded(new \DateTime());
        $field->setDateAdded(new \DateTime());
        $field->setDateModified(new \DateTime());
        $field->setType($type);
        if (!empty($charLength)) {
            $field->setCharLengthLimit($charLength);
        }
        $field->setObject('lead');
        isset($parameters['properties']) && $field->setProperties($parameters['properties']);

        return $field;
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function provideFieldLength(): iterable
    {
        yield [ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH, ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH];
        yield [64, null];
    }
}
