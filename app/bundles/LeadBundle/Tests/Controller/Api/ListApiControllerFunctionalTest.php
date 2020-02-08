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

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        $segmentId = $response['list']['id'];

        $this->assertGreaterThan(0, $segmentId);
        $this->assertEquals($payload['name'], $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('PATCH', "/api/segments/{$segmentId}/edit", ['name' => 'API segment renamed']);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the edited one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);
    }
}
