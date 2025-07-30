<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BooleanFieldIntegrationTest extends MauticMysqlTestCase
{
    public function testBooleanFieldFormSubmission(): void
    {
        // Create a form with a boolean field
        $payload = [
            'name'        => 'Boolean Test Form',
            'description' => 'Form with boolean field',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Test Boolean',
                    'type'         => 'boolean',
                    'alias'        => 'test_boolean',
                    'properties'   => [
                        'yes' => 'Custom Yes',
                        'no' => 'Custom No',
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        
        if ($clientResponse->getStatusCode() !== Response::HTTP_CREATED) {
            $this->fail('Form creation failed: ' . $clientResponse->getContent());
        }
        
        $formId         = $response['form']['id'];
        $formAlias      = $response['form']['alias'];

        // Submit the form with the positive option
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        
        $form = $formCrawler->form();
        
        // Set the value to the positive option (should be '1' for yes)
        $form->setValues([
            'mauticform[test_boolean]' => '1',
        ]);
        
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Verify the submission was saved correctly
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        $formEntity = static::getContainer()->get('mautic.form.model.form')->getEntity($formId);
        
        $submissions = $formEntity->getSubmissions();
        $this->assertCount(1, $submissions);
        
        $submission = $submissions->first();
        $results = $submission->getResults();
        
        // The result should be '1' for the positive option
        $this->assertEquals('1', $results['test_boolean']);
    }

    public function testBooleanFieldWithBlankLabels(): void
    {
        // Create a form with a boolean field where one label is blank
        $payload = [
            'name'        => 'Boolean Test Form Blank',
            'description' => 'Form with boolean field and blank label',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Test Boolean',
                    'type'         => 'boolean',
                    'alias'        => 'test_boolean',
                    'properties'   => [
                        'yes' => 'Custom Yes',
                        'no' => '',
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        
        if ($clientResponse->getStatusCode() !== Response::HTTP_CREATED) {
            $this->fail('Form creation failed: ' . $clientResponse->getContent());
        }
        
        $formId         = $response['form']['id'];
        $formAlias      = $response['form']['alias'];

        // Submit the form
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        
        $form = $formCrawler->form();
        
        // Set the value to the positive option (should be '1' for yes)
        $form->setValues([
            'mauticform[test_boolean]' => '1',
        ]);
        
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Verify the submission was saved correctly
        $formEntity = static::getContainer()->get('mautic.form.model.form')->getEntity($formId);
        $submissions = $formEntity->getSubmissions();
        $this->assertCount(1, $submissions);
        
        $submission = $submissions->first();
        $results = $submission->getResults();
        
        // The result should be '1' for the positive option
        $this->assertEquals('1', $results['test_boolean']);
    }
} 