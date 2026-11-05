<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class FieldControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testFieldListOffersIndexedAndUniqueQuickFilters(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields');

        $this->assertResponseIsSuccessful();
        $filterButton = $crawler->filter('button[data-toggle="popover"]');
        $this->assertCount(1, $filterButton);
        $filterContent = $filterButton->attr('data-content');
        $this->assertNotNull($filterContent);
        $filters = new Crawler($filterContent);
        $this->assertCount(1, $filters->filter('[data-filter="is:indexed"]'));
        $this->assertCount(1, $filters->filter('[data-filter="is:unique"]'));
    }

    #[DataProvider('quickFilterSearchProvider')]
    public function testQuickFiltersSelectMatchingFields(string $search, bool $indexed, bool $unique): void
    {
        $matching = new LeadField();
        $matching->setLabel('Matching audit field');
        $matching->setAlias('matching_audit_field');
        $matching->setType('text');
        $matching->setIsIndex($indexed);
        $matching->setIsUniqueIdentifer($unique);
        $other = new LeadField();
        $other->setLabel('Other audit field');
        $other->setAlias('other_audit_field');
        $other->setType('text');
        $this->em->persist($matching);
        $this->em->persist($other);
        $this->em->flush();

        foreach (['', '&tmpl=list'] as $template) {
            $this->client->request(Request::METHOD_GET, '/s/contacts/fields?search='.$search.$template);
            $this->assertResponseIsSuccessful();
            $content = (string) $this->client->getResponse()->getContent();
            $this->assertStringContainsString($matching->getLabel(), $content);
            $this->assertStringNotContainsString($other->getLabel(), $content);
        }
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function quickFilterSearchProvider(): iterable
    {
        yield 'indexed' => ['is:indexed', true, false];
        yield 'unique' => ['is:unique', false, true];
        yield 'combined' => ['is:indexed%20is:unique', true, true];
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldFailure(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'The leading Drupal Cloud platform to securely develop, deliver, and run websites, applications, and content. Top-of-the-line hosting options are paired with automated testing and development tools. Documentation is also included for the following components';
        $form['leadfield[label]']->setValue($label);
        $crawler = $this->client->submit($form);

        $labelErrorMessage             = trim($crawler->filter('#leadfield_label')->nextAll()->text());
        $maxLengthErrorMessageTemplate = 'Label value cannot be longer than 191 characters';

        $this->assertSame($maxLengthErrorMessageTemplate, $labelErrorMessage);
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldSuccess(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'Test value for custom field 4';
        $form['leadfield[label]']->setValue($label);
        $this->client->submit($form);

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        $this->assertInstanceOf(LeadField::class, $field);
    }

    public function testCloneFieldSubmission(): void
    {
        $field = new LeadField();
        $field->setLabel('Field to be cloned');
        $field->setAlias('field_to_be_cloned');
        $field->setType('text');

        self::getContainer()->get(LeadFieldRepository::class)->saveEntity($field);
        $this->em->clear();

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => 'field_to_be_cloned']);
        $this->assertInstanceOf(LeadField::class, $field);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/clone/'.$field->getId());

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'New Custom Field');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue('Cloned Field');

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $clonedField = $this->em->getRepository(LeadField::class)->findOneBy(['label' => 'Cloned Field']);
        $this->assertInstanceOf(LeadField::class, $clonedField);
        $this->assertNotEquals($field->getId(), $clonedField->getId());
    }

    public function testCloneNonExistentField(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/contacts/fields/clone/9999');
        $this->assertResponseStatusCodeSame(404);
    }

    #[DataProvider('getStringTypeFieldsArray')]
    public function testMaxCharLengthFieldValidationOnStringTypeWhenAddingCustomFieldFailure(string $label, string $type): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[object]']->setValue('lead');
        $form['leadfield[type]']->setValue($type);
        $form['leadfield[charLengthLimit]']->setValue('260');
        $crawler = $this->client->submit($form);

        $errorMessage             = trim($crawler->filter('#leadfield_charLengthLimit')->nextAll()->text());
        $maxCharLimitErrorMessage = 'This value should be between 1 and 191.';

        $this->assertSame($maxCharLimitErrorMessage, $errorMessage);
    }

    #[DataProvider('getStringTypeFieldsArray')]
    public function testMaxCharLengthFieldValidationOnStringTypeWhenAddingCustomFieldSuccess(string $label, string $type): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[object]']->setValue('lead');
        $form['leadfield[type]']->setValue($type);
        $form['leadfield[charLengthLimit]']->setValue('191');
        $this->client->submit($form);

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        $this->assertInstanceOf(LeadField::class, $field);
    }

    /**
     * @return array<mixed, mixed>
     */
    public static function getStringTypeFieldsArray(): iterable
    {
        yield ['test_email', 'email'];
        yield ['test_text', 'text'];
    }

    #[DataProvider('getCustomFields')]
    public function testCustomFieldCharacterLengthLimit(string $label, string $type): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[object]']->setValue('lead');
        $form['leadfield[type]']->setValue($type);
        $this->client->submit($form);

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        $this->assertInstanceOf(LeadField::class, $field);

        /** @var ColumnSchemaHelper $helper */
        $helper = $this->getContainer()->get(ColumnSchemaHelper::class);

        // Table name to check the fields.
        $name         = 'leads';
        $schemaHelper = $helper->setName($name);

        /** @var Column $fieldsDescription */
        $fieldsDescription = $schemaHelper->getColumns()[$label];

        $this->assertSame(191, $fieldsDescription->getLength());
    }

    /**
     * @return array<mixed, mixed>
     */
    public static function getCustomFields(): iterable
    {
        yield ['test_timezone', 'timezone'];
        yield ['test_locale', 'locale'];
        yield ['test_country', 'country'];
        yield ['test_phone', 'tel'];
    }
}
