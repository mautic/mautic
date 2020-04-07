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
                'email'     => 'batchemail1@email.com',
                'firstname' => 'BatchUpdate',
                'points'    => 4,
                'tags'      => ['batchtest', 'testbatch'],
            ],
            [
                'email'     => 'batchemail2@email.com',
                'firstname' => 'BatchUpdate2',
                'tags'      => ['batchtest', 'testbatch', 'batchremovetest'],
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

        // Emulate an unsanitized email to ensure that doesn't cause duplicates
        $payload[0]['email'] = 'batchemail1@email.com,';

        // Set first name as null - Mautic should keep the value
        $payload[0]['firstname'] = null;

        // Remove tags from contact 1 to see if they will stick in the database
        unset($payload[0]['tags']);

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
    }

    public function testSingleNewEndpointCreateAndUpdate()
    {
        $payload = [
            'email'     => 'apiemail1@email.com',
            'firstname' => 'API Update',
            'lastname'  => 'customlastname',
            'points'    => 4,
            'tags'      => ['apitest', 'testapi'],
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
    }

    public function testBachdDncAddAndRemove()
    {
        // Create contact
        $emailAddress = uniqid('', false).'@mautic.com';

        $payload = [
            'id'   => 80,
            'email'=> $emailAddress,
        ];

        $this->client->request('POST', '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        // Batch update contact with new DNC record
        $payload = [[
            'id'           => $contactId,
            'email'        => $emailAddress,
            'doNotContact' => [[
                'reason'    => DoNotContact::MANUAL,
                'comments'  => 'manually',
                'channel'   => 'email',
                'channelId' => null,
            ]],
        ]];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(3, $response['contacts'][0]['doNotContact'][0]['reason']);

        // Batch update contact and remove DNC record
        $payload = [[
            'id'           => $contactId,
            'email'        => $emailAddress,
            'doNotContact' => [[
                'reason'    => DoNotContact::IS_CONTACTABLE,
                'comments'  => 'manually',
                'channel'   => 'email',
                'channelId' => null,
            ]],
        ]];

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
