<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CustomFieldSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private const CUSTOM_FIELD_ALIAS = 'test_select_field';

    protected $useCleanupRollback = false;

    /**
     * Test the complete workflow:
     * 1. Create a custom field of type select with 4 options
     * 2. Create a form and add field of type select mapped to the custom field using syncList
     * 3. Create a landing page and use form using the {form=ID} token
     * 4. Edit custom field and remove one option and update
     * 5. Assert that the form in the landing page has updated list of options
     */
    public function testFormFieldOptionsUpdateWhenCustomFieldOptionsChange(): void
    {
        // Step 1: Create a custom field of type select with 4 options
        $customFieldId = $this->createCustomSelectField();

        // Step 2: Create a form with a field mapped to the custom field using syncList
        $formId = $this->createFormWithMappedField();

        // Step 3: Create a landing page with the form
        $this->createLandingPage($formId);

        // Build initial form cache by loading the page
        $crawler = $this->client->request(Request::METHOD_GET, '/test-page');
        $this->assertResponseIsSuccessful();

        // Verify initial options are present (5 total: 1 empty + 4 options)
        $selectOptions = $crawler->filter('select option');
        $this->assertCount(5, $selectOptions, 'Should have 5 options total (1 empty + 4 custom)');

        // Verify specific options by value
        $this->assertCount(1, $crawler->filter('select option[value="option1"]'), 'Option 1 should be present');
        $this->assertCount(1, $crawler->filter('select option[value="option2"]'), 'Option 2 should be present');
        $this->assertCount(1, $crawler->filter('select option[value="option3"]'), 'Option 3 should be present');
        $this->assertCount(1, $crawler->filter('select option[value="option4"]'), 'Option 4 should be present');

        $fieldCollector = $this->getContainer()->get(\Mautic\FormBundle\Collector\FieldCollector::class);
        $fieldCollector->reset(); // clear field cache to ensure fresh data. Happens naturally between requests in real usage.

        // Step 4: Edit custom field and remove one option
        $this->updateCustomFieldOptions($customFieldId);

        // Step 5: Refresh the page and assert the form has updated options
        $crawler = $this->client->request(Request::METHOD_GET, '/test-page');
        $this->assertResponseIsSuccessful();

        // Verify that the form now only has 4 options total (1 empty + 3 custom) after option removal
        $selectOptions = $crawler->filter('select option');
        // echo $crawler->html(); // Debug output to inspect the HTML
        $this->assertCount(4, $selectOptions, 'Should have 4 options total after removing one option (1 empty + 3 custom)');

        // Verify the removed option (option4) is no longer present
        $this->assertCount(0, $crawler->filter('select option[value="option4"]'), 'Option 4 should be removed');

        // Verify remaining options are still present
        $this->assertCount(1, $crawler->filter('select option[value="option1"]'), 'Option 1 should still be present');
        $this->assertCount(1, $crawler->filter('select option[value="option2"]'), 'Option 2 should still be present');
        $this->assertCount(1, $crawler->filter('select option[value="option3"]'), 'Option 3 should still be present');
    }

    private function createCustomSelectField(): int
    {
        $fieldPayload = [
            'label'      => 'Test Select Field',
            'alias'      => self::CUSTOM_FIELD_ALIAS,
            'type'       => 'select',
            'group'      => 'core',
            'object'     => 'lead',
            'properties' => [
                'list' => [
                    ['label' => 'Option 1', 'value' => 'option1'],
                    ['label' => 'Option 2', 'value' => 'option2'],
                    ['label' => 'Option 3', 'value' => 'option3'],
                    ['label' => 'Option 4', 'value' => 'option4'],
                ],
            ],
        ];

        $this->client->request('POST', '/api/fields/contact/new', $fieldPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);

        return $response['field']['id'];
    }

    private function createFormWithMappedField(): int
    {
        $formPayload = [
            'name'     => 'Test Form with Mapped Field',
            'formType' => 'standalone',
            'fields'   => [
                [
                    'label'        => 'Test Select',
                    'alias'        => 'test_select',
                    'type'         => 'select',
                    'leadField'    => self::CUSTOM_FIELD_ALIAS,
                    'mappedObject' => 'contact',
                    'mappedField'  => self::CUSTOM_FIELD_ALIAS,
                    'properties'   => [
                        'syncList' => 1,
                    ],
                ],
                [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
            'postAction' => 'return',
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);

        return $response['form']['id'];
    }

    private function createLandingPage(int $formId): void
    {
        $pagePayload = [
            'title'       => 'Test Page',
            'alias'       => 'test-page',
            'description' => 'Test page with form',
            'isPublished' => true,
            'customHtml'  => '<!DOCTYPE html>
                <html>
                    <head>
                        <title>Test Page</title>
                    </head>
                    <body>
                        <div class="container">
                            <div>{form='.$formId.'}</div>
                        </div>
                    </body>
                </html>',
        ];

        $this->client->request('POST', '/api/pages/new', $pagePayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    private function updateCustomFieldOptions(int $customFieldId): void
    {
        // Update the field to remove one option (Option 4)
        $updatePayload = [
            'properties' => [
                'list' => [
                    ['label' => 'Option 1', 'value' => 'option1'],
                    ['label' => 'Option 2', 'value' => 'option2'],
                    ['label' => 'Option 3', 'value' => 'option3'],
                    // Option 4 removed
                ],
            ],
        ];

        $this->client->request('PATCH', "/api/fields/contact/{$customFieldId}/edit", $updatePayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }
}
