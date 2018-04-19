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

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class LeadApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testBatchNewEndpointDoesNotCreateDuplicates()
    {
        $payload = [
            [
                'email'     => 'batchemail1@email.com',
                'firstname' => 'BatchUpdate',
            ],
            [
                'email'     => 'batchemail2@email.com',
                'firstname' => 'BatchUpdate2',
            ],
            [
                'email'     => 'batchemail3@email.com',
                'firstname' => 'BatchUpdate3',
            ],
        ];

        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Codes::HTTP_CREATED, $response['statusCodes'][0]);
        $this->assertEquals(Codes::HTTP_CREATED, $response['statusCodes'][1]);
        $this->assertEquals(Codes::HTTP_CREATED, $response['statusCodes'][2]);

        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Codes::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals(Codes::HTTP_OK, $response['statusCodes'][1]);
        $this->assertEquals(Codes::HTTP_OK, $response['statusCodes'][2]);
    }
}
