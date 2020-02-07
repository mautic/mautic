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

class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testSingleNewEndpointCreateAndUpdate()
    {
        $payload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
        ];

        $this->client->request('POST', '/api/segments/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $id             = $response['segment']['id'];

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        // $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        // $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        // $this->assertEquals(4, $response['contact']['points']);
        // $this->assertEquals(2, count($response['contact']['tags']));

        // Lets try to create the same contact to see that the values are not re-setted
        // $this->client->request('POST', '/api/contacts/new', ['email' => 'apiemail1@email.com']);
        // $clientResponse = $this->client->getResponse();
        // $response       = json_decode($clientResponse->getContent(), true);

        // $this->assertEquals($contactId, $response['contact']['id']);
        // $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        // $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        // $this->assertEquals(4, $response['contact']['points']);
        // $this->assertEquals(2, count($response['contact']['tags']));
    }
}
