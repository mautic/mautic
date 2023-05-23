<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class LeadApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled'] = 'testDisabledApi' !== $this->getName();

        parent::setUp();
    }

    public function testDisabledApi(): void
    {
        $this->client->request('POST', '/api/contacts/new', ['email' => 'apiemail1@email.com']);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals(
            '{"errors":[{"message":"API disabled. You need to enable the API in the API settings of Mautic\u0027s Configuration.","code":403,"type":"api_disabled"}]}',
            $clientResponse->getContent()
        );
    }

    public function testBatchNewEndpointDoesNotCreateDuplicates(): void
    {
        $payload = [
            [
                'email'            => 'batchemail1@email.com',
                'firstname'        => 'BatchUpdate',
                'points'           => 4,
                'tags'             => ['batchtest', 'testbatch'],
                'city'             => 'Houston',
                'state'            => 'Texas',
                'country'          => 'United States',
                'preferred_locale' => 'es_SV',
                'timezone'         => 'America/Chicago',
                'owner'            => 1,
            ],
            [
                'email'            => 'batchemail2@email.com',
                'firstname'        => 'BatchUpdate2',
                'tags'             => ['batchtest', 'testbatch', 'batchremovetest'],
                'city'             => 'Boston',
                'state'            => 'Massachusetts',
                'country'          => 'United States',
                'preferred_locale' => 'en_GB',
                'timezone'         => 'America/New_York',
            ],
            [
                'email'     => 'batchemail3@email.com',
                'firstname' => 'BatchUpdate3',
            ],
        ];

        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Assert status codes
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $contactId1 = $response['contacts'][0]['id'];
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1]);
        $contactId2 = $response['contacts'][1]['id'];
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2]);
        $contactId3 = $response['contacts'][2]['id'];

        // Assert email
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[1]['email'], $response['contacts'][1]['fields']['all']['email']);
        $this->assertEquals($payload[2]['email'], $response['contacts'][2]['fields']['all']['email']);

        // Assert firstname
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($payload[1]['firstname'], $response['contacts'][1]['fields']['all']['firstname']);
        $this->assertEquals($payload[2]['firstname'], $response['contacts'][2]['fields']['all']['firstname']);

        // Assert points while also testing empty precision as points is treated as a custom field
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertSame(0, $response['contacts'][1]['points']);
        $this->assertSame(0, $response['contacts'][2]['points']);
        $this->assertSame(0, $response['contacts'][2]['fields']['all']['points']);

        // Assert tags
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals(3, count($response['contacts'][1]['tags']));
        $this->assertEquals(0, count($response['contacts'][2]['tags']));

        // Assert city
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[1]['city'], $response['contacts'][1]['fields']['all']['city']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['city']);

        // Assert state
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[1]['state'], $response['contacts'][1]['fields']['all']['state']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['state']);

        // Assert country
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[1]['country'], $response['contacts'][1]['fields']['all']['country']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['country']);

        // Assert preferred_locale
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[1]['preferred_locale'], $response['contacts'][1]['fields']['all']['preferred_locale']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['preferred_locale']);

        // Assert timezone
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[1]['timezone'], $response['contacts'][1]['fields']['all']['timezone']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['timezone']);

        // Assert owner
        $this->assertEquals($payload[0]['owner'], $response['contacts'][0]['owner']['id']);
        $this->assertEquals(null, $response['contacts'][1]['owner']);
        $this->assertEquals(null, $response['contacts'][2]['owner']);

        // Emulate an unsanitized email to ensure that doesn't cause duplicates
        $payload[0]['email'] = 'batchemail1@email.com,';

        // Set first name as null - Mautic should keep the value
        $payload[0]['firstname'] = null;

        // Remove tags from contact 1 to see if they will stick in the database
        unset($payload[0]['tags']);

        // Update others
        $payload[0]['city']             = 'Sunnyvale';
        $payload[0]['state']            = 'California';
        $payload[0]['timezone']         = 'America/Los_Angeles';
        $payload[0]['preferred_locale'] = 'en_US';

        // Update owner
        $payload[0]['owner'] = null;
        $payload[1]['owner'] = 1;

        // Set some tags to contact 2 to see if tags update
        $payload[1]['tags'] = ['testbatch1', 'testbatch2', '-batchremovetest'];

        // Set some points to contact 2 to see if they update
        $payload[1]['points'] = 3;

        // Update the 3 contacts
        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals($contactId1, $response['contacts'][0]['id']);
        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][1]);
        $this->assertEquals($contactId2, $response['contacts'][1]['id']);
        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][2]);
        $this->assertEquals($contactId3, $response['contacts'][2]['id']);

        // Assert email
        $this->assertEquals('batchemail1@email.com', $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[1]['email'], $response['contacts'][1]['fields']['all']['email']);
        $this->assertEquals($payload[2]['email'], $response['contacts'][2]['fields']['all']['email']);

        // Assert firstname
        $this->assertEquals('BatchUpdate', $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($payload[1]['firstname'], $response['contacts'][1]['fields']['all']['firstname']);
        $this->assertEquals($payload[2]['firstname'], $response['contacts'][2]['fields']['all']['firstname']);

        // Assert points
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertSame(3, $response['contacts'][1]['points']);
        $this->assertSame(3, $response['contacts'][1]['fields']['all']['points']);
        $this->assertSame(0, $response['contacts'][2]['points']);
        $this->assertSame(0, $response['contacts'][2]['fields']['all']['points']);

        // Assert tags
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals(4, count($response['contacts'][1]['tags']));
        $this->assertEquals(0, count($response['contacts'][2]['tags']));

        // Assert city
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[1]['city'], $response['contacts'][1]['fields']['all']['city']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['city']);

        // Assert state
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[1]['state'], $response['contacts'][1]['fields']['all']['state']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['state']);

        // Assert country
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[1]['country'], $response['contacts'][1]['fields']['all']['country']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['country']);

        // Assert preferred_locale
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[1]['preferred_locale'], $response['contacts'][1]['fields']['all']['preferred_locale']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['preferred_locale']);

        // Assert timezone
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[1]['timezone'], $response['contacts'][1]['fields']['all']['timezone']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['timezone']);

        // Assert owner
        $this->assertEquals(null, $response['contacts'][0]['owner']);
        $this->assertEquals($payload[1]['owner'], $response['contacts'][1]['owner']['id']);
        $this->assertEquals(null, $response['contacts'][2]['owner']);
    }

    /**
     * If there are some entities to return then the response returns a hash table (JSON object),
     * So for response with no entities we must also return a JSON object because some languages
     * decode it differently then emtpty array.
     */
    public function testEmptyResponseReturnsJsonObject(): void
    {
        $this->client->request('GET', '/api/contacts?where[0][val]=unicorn&where[0][col]=email&where[0][expr]=eq');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals('{"total":"0","contacts":{}}', $clientResponse->getContent());
    }

    public function testBatchEditEndpoint(): void
    {
        $contact = new Lead();
        $contact->setEmail('batcheditcontact1@gmail.com');

        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        $payload = [
            ['email' => 'batcheditcontact1-updated@gmail.com', 'id' => $contact->getId()],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals($contact->getId(), $response['contacts'][0]['id']);
        $this->assertEquals('batcheditcontact1-updated@gmail.com', $response['contacts'][0]['fields']['all']['email']);
    }

    public function testBatchEditEndpointWithRubbishId(): void
    {
        $payload = [
            ['email' => 'batchemail1@email.com', 'id' => 'rubbish'],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $this->assertGreaterThanOrEqual(1, $response['contacts'][0]['id']);
        $this->assertEquals('batchemail1@email.com', $response['contacts'][0]['fields']['all']['email']);
    }

    public function testSingleNewEndpointCreateAndUpdate(): void
    {
        $payload = [
            'email'            => 'apiemail1@email.com',
            'firstname'        => 'API',
            'lastname'         => 'Update',
            'points'           => 4,
            'tags'             => ['apitest', 'testapi'],
            'city'             => 'Houston',
            'state'            => 'Texas',
            'country'          => 'United States',
            'preferred_locale' => 'es_SV',
            'timezone'         => 'America/Chicago',
            'owner'            => 1,
        ];

        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEquals($payload['lastname'], $response['contact']['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($payload['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($payload['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($payload['owner'], $response['contact']['owner']['id']);

        // without overwriteWithBlank lastname is not set empty
        $payload['lastname'] = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertNotEmpty($response['contact']['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));

        // with overwriteWithBlank lastname is empty
        $payload['overwriteWithBlank'] = true;
        $payload['lastname']           = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEmpty($response['contact']['fields']['all']['lastname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($payload['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($payload['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($payload['owner'], $response['contact']['owner']['id']);

        // Lets try to create the same contact and it should merge based on unique identifier (email)
        $updatedValues = [
            'email'    => 'apiemail1@email.com',
            'lastname' => 'Update',
            'city'     => 'Boston',
            'state'    => 'Massachusetts',
            'owner'    => 2,
        ];

        $this->client->request('POST', '/api/contacts/new', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($updatedValues['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEquals($updatedValues['lastname'], $response['contact']['fields']['all']['lastname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);

        // Test getting a contact
        $this->client->request(
            'GET', '/api/contacts/'.$contactId);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);

        // Test fetching the batch of contacts
        $this->client->request(
            'GET', '/api/contacts');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue(isset($response['contacts'][$contactId]));
        $contact = $response['contacts'][$contactId];
        $this->assertEquals($contactId, $contact['id']);
        $this->assertEquals($payload['email'], $contact['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $contact['fields']['all']['firstname']);
        $this->assertSame(4, $contact['points']);
        $this->assertSame(4, $contact['fields']['all']['points']);
        $this->assertEquals(2, count($contact['tags']));
        $this->assertEquals($updatedValues['city'], $contact['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $contact['fields']['all']['state']);
        $this->assertEquals($payload['country'], $contact['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $contact['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $contact['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $contact['owner']['id']);

        // Test patch and values should be updated
        $updatedValues = [
            'email'  => 'apiemail1@email.com',
            'city'   => 'Boston',
            'state'  => 'Massachusetts',
            'owner'  => 2,
            'points' => 1,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contactId),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($updatedValues['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertSame(1, $response['contact']['points']);
        $this->assertSame(1, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);
    }

    /**
     * Test creating a new contact with doNotContact information.
     * The API response should include DNC information.
     */
    public function testSingleNewEndpointCreateAndDeleteWithDnc(): void
    {
        $payload = [
            'email'            => 'apidnc@email.com',
            'firstname'        => 'API',
            'lastname'         => 'DNC test',
            'points'           => 4,
            'tags'             => ['apitest', 'testapi'],
            'city'             => 'Houston',
            'state'            => 'Texas',
            'country'          => 'United States',
            'preferred_locale' => 'es_SV',
            'timezone'         => 'America/Chicago',
            'owner'            => 1,
            'doNotContact'     => [
                [
                    'channel' => 'email',
                    'reason'  => DoNotContact::BOUNCED,
                ],
            ],
        ];
        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        $this->assertEquals(1, count($response['contact']['doNotContact']));
        $this->assertEquals($payload['doNotContact'][0]['channel'], $response['contact']['doNotContact'][0]['channel']);
        $this->assertEquals($payload['doNotContact'][0]['reason'], $response['contact']['doNotContact'][0]['reason']);

        // Remove contact
        $this->client->request('DELETE', "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    public function testBatchDncAddAndRemove(): void
    {
        // Create contact
        $emailAddress = uniqid('', false).'@mautic.com';

        $payload = [
            'id'    => 80,
            'email' => $emailAddress,
        ];

        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        // Batch update contact with new DNC record
        $payload = [
            [
                'id'           => $contactId,
                'email'        => $emailAddress,
                'doNotContact' => [
                    [
                        'reason'    => DoNotContact::MANUAL,
                        'comments'  => 'manually',
                        'channel'   => 'email',
                        'channelId' => null,
                    ],
                ],
            ],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(3, $response['contacts'][0]['doNotContact'][0]['reason']);

        // Batch update contact and remove DNC record
        $payload = [
            [
                'id'           => $contactId,
                'email'        => $emailAddress,
                'doNotContact' => [
                    [
                        'reason'    => DoNotContact::IS_CONTACTABLE,
                        'comments'  => 'manually',
                        'channel'   => 'email',
                        'channelId' => null,
                    ],
                ],
            ],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEmpty($response['contacts'][0]['doNotContact']);

        // Remove contact
        $this->client->request('DELETE', "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    public function testAddAndRemoveDncToExistingContact(): void
    {
        // Create contact
        $payload = [
            'email' => 'addDncDemo@mautic.org',
        ];

        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        // Ensure that doNotContact is empty after creating the contact
        $this->assertSame([], $response['contact']['doNotContact']);

        // Check with a DNC payload that has an empty reasoncode in it, this should throw a 400 Bad Request error
        $dncPayload = [
            'reason' => 0,
        ];
        $dncChannel = 'email';

        $this->client->request('POST', "/api/contacts/$contactId/dnc/$dncChannel/add", $dncPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());

        // Leave the DNC payload empty to ensure it takes default values for channel and reason.
        $dncPayload = [];

        // Add DNC to the contact.
        $this->client->request('POST', "/api/contacts/$contactId/dnc/$dncChannel/add", $dncPayload);
        $clientResponse = $this->client->getResponse();
        $dncResponse    = json_decode($clientResponse->getContent(), true);

        // MANUAL (3) is the default value according to the dev docs: https://developer.mautic.org/#add-do-not-contact
        $this->assertSame(DoNotContact::MANUAL, $dncResponse['contact']['doNotContact'][0]['reason']);
        $this->assertSame($dncChannel, $dncResponse['contact']['doNotContact'][0]['channel']);

        // Remove DNC from the contact.
        $this->client->request('POST', "/api/contacts/$contactId/dnc/$dncChannel/remove");
        $clientResponse    = $this->client->getResponse();
        $dncRemoveResponse = json_decode($clientResponse->getContent(), true);

        $this->assertSame([], $dncRemoveResponse['contact']['doNotContact']);

        // Remove the contact.
        $this->client->request('DELETE', "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
    }
}
