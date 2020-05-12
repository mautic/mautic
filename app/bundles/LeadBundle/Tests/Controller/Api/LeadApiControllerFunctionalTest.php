<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Response;

class LeadApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testBatchNewEndpointDoesNotCreateDuplicates()
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

        // Assert points
        $this->assertEquals(4, $response['contacts'][0]['points']);
        $this->assertEquals(0, $response['contacts'][1]['points']);
        $this->assertEquals(0, $response['contacts'][2]['points']);

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
        $this->assertEquals(4, $response['contacts'][0]['points']);
        $this->assertEquals(3, $response['contacts'][1]['points']);
        $this->assertEquals(0, $response['contacts'][2]['points']);

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

    public function testSingleNewEndpointCreateAndUpdate()
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
        $this->assertEquals(4, $response['contact']['points']);
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
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);

        // Test patch and values should be updated
        $updatedValues = [
            'email' => 'apiemail1@email.com',
            'city'  => 'Boston',
            'state' => 'Massachusetts',
            'owner' => 2,
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
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);
    }

    public function testBachdDncAddAndRemove()
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
}
