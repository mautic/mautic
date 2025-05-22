<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;

class FieldControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldFailure(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'The leading Drupal Cloud platform to securely develop, deliver, and run websites, applications, and content. Top-of-the-line hosting options are paired with automated testing and development tools. Documentation is also included for the following components';
        $form['leadfield[label]']->setValue($label);
        $crawler = $this->client->submit($form);

        $labelErrorMessage             = trim($crawler->filter('#leadfield_label')->nextAll()->text());
        $maxLengthErrorMessageTemplate = 'Label value cannot be longer than 191 characters';

        $this->assertEquals($maxLengthErrorMessageTemplate, $labelErrorMessage);
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldSuccess(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'Test value for custom field 4';
        $form['leadfield[label]']->setValue($label);
        $crawler = $this->client->submit($form);

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['label' => $label]);
        $this->assertNotNull($field);
    }

    public function testAbortColumnCreateExceptionIsHandledOnEditAction(): void
    {
        // First create a field
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');
        $form  = $crawler->selectButton('Save & Close')->form();
        $label = 'Test field for edit exception';
        $alias = 'test_field_edit_exception';
        $form['leadfield[label]']->setValue($label);
        $form['leadfield[alias]']->setValue($alias);
        $this->client->submit($form);

        // Get the created field
        $field = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => $alias]);
        $this->assertNotNull($field);

        // Configure BackgroundSettings to process in background
        $container = static::getContainer();
        $backgroundSettings = $container->get('mautic.lead.field.settings.background_settings');
        $reflection = new \ReflectionClass($backgroundSettings);
        $property = $reflection->getProperty('processInBackground');
        $property->setAccessible(true);
        $property->setValue($backgroundSettings, true);

        // Now edit the field which should trigger the exception
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/edit/'.$field->getId());
        $form = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue($label . ' edited');
        
        $crawler = $this->client->submit($form);
        
        // Check that we were redirected back to the index with the correct flash message
        $this->assertTrue($this->client->getResponse()->isRedirect('/s/contacts/fields'));
        
        // Follow the redirect
        $crawler = $this->client->followRedirect();
        
        // Check for the flash message
        $flashMessages = $crawler->filter('.alert-notice');
        $this->assertCount(1, $flashMessages);
        $this->assertStringContainsString('mautic.lead.field.pushed_to_background', $flashMessages->text());
    }
}
