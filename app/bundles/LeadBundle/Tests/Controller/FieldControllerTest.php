<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldControllerTest extends MauticMysqlTestCase
{
    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldFailure()
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue('The leading Drupal Cloud platform to securely develop, deliver, and run websites, applications, and content. Top-of-the-line hosting options are paired with automated testing and development tools. Documentation is also included for the following components');

        $crawler    = $this->client->submit($form);

        $labelErrorMessage = trim($crawler->filter('#leadfield_label')->nextAll()->text());
        // $maxLengthErrorMessageTemplate = $this->container->get('translator')->trans('mautic.lead.field.label.maxlength',[]);
        $maxLengthErrorMessageTemplate = 'Label value cannot be longer than 191 characters';
        $this->assertEquals($maxLengthErrorMessageTemplate, $labelErrorMessage);

        // $this->assertContains('Label value cannot be longer than 192 characters', $this->client->getResponse()->getContent());
    }

    public function testLengthValidationOnLabelFieldWhenAddingCustomFieldSuccess()
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/new');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['leadfield[label]']->setValue('Random Department 3');

        $crawler    = $this->client->submit($form);

        // check if the submitted field is in list
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/fields/2');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
}
