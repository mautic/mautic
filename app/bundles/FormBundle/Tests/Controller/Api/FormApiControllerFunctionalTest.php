<?php

namespace Mautic\FormBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testFormWorkflow()
    {
        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'Email',
                    'alias'     => 'email',
                    'type'      => 'text',
                    'leadField' => 'email',
                ],
            ],
        ];

        // Create:
        $this->client->request('POST', '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), 'Return code must be 201.');

        $formId = $response['form']['id'];
        $this->assertGreaterThan(0, $formId);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        for ($i = 0; $i < count($payload['fields']); ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($payload['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
        }

        // Edit:
        $this->client->request('PATCH', "/api/forms/{$formId}/edit", ['name' => 'Form API renamed']);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($formId, $response['form']['id'], 'ID of the created form does not match with the edited one.');
        $this->assertEquals('Form API renamed', $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        for ($i = 0; $i < count($payload['fields']); ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($payload['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
        }

        // Get:
        $this->client->request('GET', "/api/forms/{$formId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($formId, $response['form']['id'], 'ID of the created form does not match with the fetched one.');
        $this->assertEquals('Form API renamed', $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        for ($i = 0; $i < count($payload['fields']); ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($payload['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
        }

        // Delete:
        $this->client->request('DELETE', "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/forms/{$formId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode());
        $this->assertSame(404, $response['errors'][0]['code']);
    }

    public function testFormWithChangeTagsAction()
    {
        // Create tag:
        $tag1Payload = ['tag' => 'add this'];
        $tag2Payload = ['tag' => 'remove this'];

        $this->client->request('POST', '/api/tags/new', $tag1Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tag1Id         = $response['tag']['id'];

        $this->client->request('POST', '/api/tags/new', $tag2Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tag2Id         = $response['tag']['id'];

        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'lab',
                    'alias'     => 'email',
                    'type'      => 'text',
                    'leadField' => 'email',
                ],
            ],
            'actions' => [
                [
                    'name'        => 'Add tags to contact',
                    'description' => 'action description',
                    'type'        => 'lead.changetags',
                    'order'       => 1,
                    'properties'  => [
                        'add_tags'    => [$tag1Id],
                        'remove_tags' => [$tag2Id],
                    ],
                ],
            ],
        ];

        // Create form with lead.changetags action:
        $this->client->request('POST', '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), 'Return code must be 201.');

        $formId = $response['form']['id'];
        $this->assertGreaterThan(0, $formId);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        for ($i = 0; $i < count($payload['fields']); ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($payload['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
        }
        $this->assertIsArray($response['form']['actions']);
        $this->assertCount(count($payload['actions']), $response['form']['actions']);
        $this->assertEquals($payload['actions'][0]['name'], $response['form']['actions'][0]['name']);
        $this->assertEquals($payload['actions'][0]['description'], $response['form']['actions'][0]['description']);
        $this->assertEquals($payload['actions'][0]['type'], $response['form']['actions'][0]['type']);
        $this->assertEquals($payload['actions'][0]['order'], $response['form']['actions'][0]['order']);
        $this->assertIsArray($response['form']['actions'][0]['properties']['add_tags']);
        $this->assertIsArray($response['form']['actions'][0]['properties']['remove_tags']);
        $this->assertEquals($tag1Payload['tag'], $response['form']['actions'][0]['properties']['add_tags'][0]);
        $this->assertEquals($tag2Payload['tag'], $response['form']['actions'][0]['properties']['remove_tags'][0]);
    }

    public function testFormWithDuplicateFieldAliases(): void
    {
        // Create form
        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'Email',
                    'alias'     => 'email',
                    'type'      => 'text',
                    'leadField' => 'email',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $lastValidFormId = $response['form']['id'];
        $this->assertGreaterThan(0, $lastValidFormId);
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), 'Return code must be 201.');

        // Get the last correctly saved form
        $this->client->request(Request::METHOD_GET, '/api/forms', [
            'orderBy'    => 'id',
            'orderByDir' => 'DESC',
            'limit'      => '1',
        ]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertIsArray($response['forms']);
        $this->assertCount(1, $response['forms']);
        $this->assertEquals($lastValidFormId, $response['forms'][0]['id']);

        // Try to update invalid, non-existent form
        $longAlias      = 'very_long_field_alias_12345';
        $invalidPayload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'test1',
                    'alias'     => 'very_long_field_alias_12345',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'test2',
                    'alias'     => 'very_long_field_alias_123456',
                    'type'      => 'text',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/forms/123/edit', $invalidPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $validationMessage = 'Another field is already using this alias: %alias%. Please choose another or leave blank to have it autogenerated.';
        $expectedMessage   = str_replace('%alias%', substr($longAlias, 0, 25), $validationMessage);

        $this->assertNotEmpty($response['errors'], 'No errors were returned when trying to save an invalid form');
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['errors'][0]['code'], 'Return code must be 400.');
        $this->assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode(), 'Return code must be 400.');
        $this->assertSame($expectedMessage, $response['errors'][0]['message'], 'Returned message is different than expected');

        // Get the last correctly saved form again. Make sure that trying to save invalid form didn't create a new one.
        $this->client->request(Request::METHOD_GET, '/api/forms', [
            'orderBy'    => 'id',
            'orderByDir' => 'DESC',
            'limit'      => '1',
        ]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertIsArray($response['forms']);
        $this->assertCount(1, $response['forms']);
        $this->assertEquals($lastValidFormId, $response['forms'][0]['id'], 'An attempt to save invalid form created a new entity');
    }

    public function testFormWithInvalidField(): void
    {
        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'test1',
                    'alias'     => 'test1',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'test2',
                    'id'        => 123,
                    'alias'     => 'test2',
                    'type'      => 'invalidField',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/forms/123/edit', $payload);
        $response        = $this->client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertNotEmpty($responseContent->errors, 'No errors were returned when trying to save an invalid form');
        $this->assertSame('Form Field ID 123 not found', $responseContent->errors[0]->message);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), 'Return code must be 404.');
    }
}
