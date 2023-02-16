<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class TagApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testTagWorkflow()
    {
        $tag1Payload = ['tag' => 'test_tag'];

        // Create new tag
        $this->client->request('POST', '/api/tags/new', $tag1Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tagId          = $response['tag']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), 'Return code must be 201.');
        $this->assertGreaterThan(0, $tagId);
        $this->assertEquals($tag1Payload['tag'], $response['tag']['tag']);

        // Try to create tag with same name
        $this->client->request('POST', '/api/tags/new', $tag1Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), 'Return code must be 200.');
        // The same tag id should be returned
        $this->assertEquals($tagId, $response['tag']['id'], 'ID of created tag with the same name does not match. Possible duplicates.');
        $this->assertEquals($tag1Payload['tag'], $response['tag']['tag']);

        // Edit tag name
        $tag1RenamePayload = ['tag' => 'tag_renamed'];
        $this->client->request('PATCH', "/api/tags/{$tagId}/edit", $tag1RenamePayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertSame($tagId, $response['tag']['id'], 'ID of the created tag does not match with the edited one.');
        $this->assertEquals($tag1RenamePayload['tag'], $response['tag']['tag']);

        // Get tag
        $this->client->request('GET', "/api/tags/{$tagId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($tagId, $response['tag']['id'], 'ID of the created tag does not match with the fetched one.');
        $this->assertEquals($tag1RenamePayload['tag'], $response['tag']['tag']);

        // Delete:
        $this->client->request('DELETE', "/api/tags/{$tagId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/tags/{$tagId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode());
        $this->assertSame(404, $response['errors'][0]['code']);
    }

    /**
     * Test if whitespace before or after tag name is removed and duplicates are not created.
     */
    public function testWhitespaceBeforeAndAfterNameNotCreatingDuplicates()
    {
        $tagName               = 'test';
        $whitespaceTestPayload = ['test', 'test ', ' test', "test\t", "\ttest"];
        $tagId                 = null;
        foreach ($whitespaceTestPayload as $payload) {
            $this->client->request('POST', '/api/tags/new', ['tag' => $payload]);
            $clientResponse = $this->client->getResponse();
            $response       = json_decode($clientResponse->getContent(), true);

            // whitespace before and after tag name should be removed, name should be the same for each tag
            $this->assertEquals($tagName, $response['tag']['tag']);
            if (null === $tagId) {
                $tagId = $response['tag']['id'];
            } else {
                $this->assertSame($tagId, $response['tag']['id'], 'ID of created tag does not match. Possible duplicates.');
            }
        }
    }

    /**
     * Test if special characters in tag name are encoded and duplicates are not created.
     */
    public function testEncodedCharactersNotCreatingDuplicates()
    {
        $tagInputName    = 'hello" world';

        $this->client->request('POST', '/api/tags/new', ['tag' => $tagInputName]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tagId          = $response['tag']['id'];
        $this->assertGreaterThan(0, $tagId);
        $this->assertEquals($tagInputName, $response['tag']['tag']);

        // Try to create duplicate
        $this->client->request('POST', '/api/tags/new', ['tag' => $tagInputName]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame($tagId, $response['tag']['id'], 'ID of created tag does not match. Possible duplicates.');
    }

    public function testTagCreationWithoutRequiredData()
    {
        // Sending an empty payload should return a 500 server error
        // TODO ensure that the server sends back a 400 status code instead
        $this->client->request('POST', '/api/tags/new', []);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(500, $clientResponse->getStatusCode());
    }
}
