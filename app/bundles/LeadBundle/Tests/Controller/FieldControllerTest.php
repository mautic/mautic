<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class FieldControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['create_custom_field_in_background'] = 'testAbortColumnCreateExceptionIsHandledOnEditAction' === $this->name();

        parent::setUp();
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

    public function testAbortColumnCreateExceptionIsHandledOnEditAction(): void
    {
        // First create a field
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');
        $form    = $crawler->selectButton('Save & Close')->form();
        $label   = 'Test field for edit exception';
        $alias   = 'test_field_edit_exception';
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[alias]']->setValue($alias);
        $crawler = $this->client->submit($form);

        // Check for successful response (2xx or 3xx status code)
        $response = $this->client->getResponse();
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());

        // Get the created field
        $field = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => $alias]);
        $this->assertNotNull($field, 'Field was not created');

        // Run the background command to create the column
        $commandTester = $this->testSymfonyCommand('mautic:custom-field:create-column', ['--id' => $field->getId()]);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Now edit the field - just change the label
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/edit/'.$field->getId());
        $form    = $crawler->selectButton('Save & Close')->form();
        $crawler = $this->client->submit($form);

        // Check for successful response (2xx or 3xx status code)
        $response = $this->client->getResponse();
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
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
