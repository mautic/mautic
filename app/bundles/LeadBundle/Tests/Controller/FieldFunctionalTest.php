<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\HttpFoundation\Request;

final class FieldFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    #[DataProvider('provideFieldLength')]
    public function testNewFieldVarcharFieldLength(int $expectedLength, ?int $inputLength = null): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = self::getContainer()->get(FieldModel::class);
        $field      = $this->createField('a', 'text', [], $inputLength);
        $fieldModel->saveEntity($field);

        $tablePrefix = self::getContainer()->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->createSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertEquals($expectedLength, $columns[$field->getAlias()]->getLength());
    }

    public function testNewMultiSelectField(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = self::getContainer()->get(FieldModel::class);
        $field      = $this->createField('s', 'select', ['properties' => ['list' => ['choice_a' => 'Choice A']]]);
        $fieldModel->saveEntity($field);

        $tablePrefix = self::getContainer()->getParameter('mautic.db_table_prefix');
        $columns     = $this->connection->createSchemaManager()->listTableColumns("{$tablePrefix}leads");
        $this->assertArrayHasKey('field_s', $columns);
    }

    public function testNewDateField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();

        $form['leadfield[label]']->setValue('Best Date Ever');
        $form['leadfield[type]']->setValue('date');

        $this->client->submit($form);

        $text = strip_tags($this->client->getResponse()->getContent());

        self::assertResponseIsSuccessful();
        $this->assertStringNotContainsString('New Custom Field', $text);
        $this->assertStringNotContainsString('This form should not contain extra fields.', $text);
        $this->assertStringContainsString('Edit Custom Field - Best Date Ever', $text);
    }

    public function testFieldDeleteValidationUsedInSegment(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel       = self::getContainer()->get(FieldModel::class);
        $field_first      = $this->createField('First');
        $fieldModel->saveEntity($field_first);

        $field_second      = $this->createField('Second');
        $fieldModel->saveEntity($field_second);

        // Create a segment which uses the custom field we just created.
        $segment = new LeadList();
        $segment->setName('Field Segment');
        $segment->setPublicName('Field Segment');
        $segment->setAlias('field_segment');
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'field_first',
                'object'     => 'lead',
                'type'       => 'text',
                'display'    => null,
                'operator'   => '=',
            ],
            [
                'glue'       => 'and',
                'field'      => 'field_second',
                'object'     => 'lead',
                'type'       => 'text',
                'display'    => null,
                'operator'   => '=',
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();

        // Try deleting single field.
        $this->client->request(Request::METHOD_POST,
            '/s/contacts/fields/delete/'.$field_first->getId(), [], [], $this->createAjaxHeaders());

        $this->assertStringContainsString('please go back and check mentioned resource(s) before deleting.', strip_tags($this->client->getResponse()->getContent()));

        // Try deleting multiple fields.
        $parameters = 'ids=["'.$field_first->getId().'","'.$field_second->getId().'"]';
        $this->client->request(Request::METHOD_POST,
            '/s/contacts/fields/batchDelete?'.$parameters, [], [], $this->createAjaxHeaders());

        $this->assertStringContainsString('cannot be deleted because they are in use by other entities.', strip_tags($this->client->getResponse()->getContent()));
    }

    public function testNewSelectField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        self::assertResponseIsSuccessful();

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

        self::assertResponseIsSuccessful();
        $this->assertStringNotContainsString('New Custom Field', $text);
        $this->assertStringNotContainsString('This form should not contain extra fields.', $text);
        $this->assertStringContainsString('Edit Custom Field - Test select field', $text);
    }

    /**
     * @param array<string, string> $properties
     */
    #[DataProvider('dataForCreatingNewBooleanField')]
    public function testCreatingNewBooleanField(array $properties, string $expectedMessage): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        self::assertResponseIsSuccessful($this->client->getResponse()->getContent());

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
        $this->assertResponseIsSuccessful();

        $text = strip_tags($this->client->getResponse()->getContent());
        $this->assertStringNotContainsString($expectedMessage, $text);
    }

    /**
     * @return iterable<string, array<int, string|array<string, string>>>
     */
    public static function dataForCreatingNewBooleanField(): iterable
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

        self::assertResponseIsSuccessful();

        // Check if the radio button with value 0 is checked and value 1 is not
        $this->assertNotNull($crawler->filter('#leadfield_default_template_boolean_0')->attr('checked'));
        $this->assertNull($crawler->filter('#leadfield_default_template_boolean_1')->attr('checked'));
    }

    public function testDefaultValueWithApostropheIsNotEncodedOnRepeatedSave(): void
    {
        $label   = 'Apostrophe default field';
        $default = "Owner's choice";

        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[type]']->setValue('text');
        $form['leadfield[defaultValue]']->setValue($default);

        $this->client->submit($form);

        /** @var LeadField|null $field */
        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        Assert::assertNotNull($field);
        Assert::assertSame($default, $field->getDefaultValue());

        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/edit/'.$field->getId());

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();
        $this->client->submit($form);

        $this->em->clear();

        /** @var LeadField|null $field */
        $field = $this->em->getRepository(LeadField::class)->find($field->getId());
        Assert::assertNotNull($field);
        Assert::assertSame($default, $field->getDefaultValue());
    }

    public function testFieldsSearchByIds(): void
    {
        $urlEncodedSearch = urlencode('ids:2,3');
        $this->client->request(Request::METHOD_GET, "/s/contacts/fields?search={$urlEncodedSearch}");
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('First Name', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Last Name', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createField(string $suffix, string $type = 'text', array $parameters = [], ?int $charLength = null): LeadField
    {
        $field = new LeadField();
        $field->setName("Field {$suffix}");
        $field->setAlias("field_{$suffix}");
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
    public static function provideFieldLength(): iterable
    {
        yield [ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH, ClassMetadataBuilder::MAX_VARCHAR_INDEXED_LENGTH];
        yield [64, null];
    }
}
