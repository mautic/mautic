<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
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

    public function testCloneFieldSubmission(): void
    {
        $field = new LeadField();
        $field->setLabel('Field to be cloned');
        $field->setAlias('field_to_be_cloned');
        $field->setType('text');

        $this->em->getRepository(LeadField::class)->saveEntity($field);
        $this->em->clear();

        $field = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => 'field_to_be_cloned']);
        $this->assertNotNull($field);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/clone/'.$field->getId());

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'New Custom Field');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue('Cloned Field');

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $clonedField = $this->em->getRepository(LeadField::class)->findOneBy(['label' => 'Cloned Field']);
        $this->assertNotNull($clonedField);
        $this->assertNotEquals($field->getId(), $clonedField->getId());
    }

    public function testCloneNonExistentField(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/contacts/fields/clone/9999');
        $this->assertResponseStatusCodeSame(404);
    }
}
