<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class ReportApiControllerTest extends MauticMysqlTestCase
{
    /**
     * Testing in a single method to decrease execution time from DB overhead.
     */
    public function testPostGetPatchPutDeleteEndPoints()
    {
        // Create a new report
//        $data = json_decode(file_get_contents(__DIR__.'/data/post.json'), true);
//        $this->client->request('POST', '/api/reports/new', $data);
//        $response     = $this->client->getResponse();
//        $responseData = json_decode($response->getContent(), true);
//        $this->assertSame(Codes::HTTP_CREATED, $response->getStatusCode());
//        $this->assertTrue(isset($responseData['report']));
//        $this->assertEquals($data['name'], $responseData['report']['name']);
//        $id     = $responseData['report']['id'];
//        $source = $data['source'];

        // Get the new report
        $this->client->request('GET', sprintf('/api/reports/%s', 5));
        $response = $this->client->getResponse();
        $this->assertSame(Codes::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue(isset($responseData['data']));
        $this->assertTrue(isset($responseData['dataColumns']));
        $this->assertTrue(isset($responseData['report']));
        //$this->assertEquals($data['name'], $responseData['report']['name']);
//
//        // Patch a report
//        $data = json_decode(file_get_contents(__DIR__.'/data/patch.json'), true);
//        $this->client->request('PATCH', sprintf('/api/reports/%s/edit', $id), $data);
//        $response = $this->client->getResponse();
//        $this->assertSame(Codes::HTTP_OK, $response->getStatusCode());
//        $responseData = json_decode($response->getContent(), true);
//        $this->assertTrue(isset($responseData['report']));
//        $this->assertEquals($source, $responseData['report']['source']);
//        $this->assertEquals($data['scheduleUnit'], $responseData['report']['scheduleUnit']);
//        $this->assertEquals($data['toAddress'], $responseData['report']['toAddress']);
//        $this->assertEquals($data['scheduleDay'], $responseData['report']['scheduleDay']);
//
//        // PUT a report
//        $data = json_decode(file_get_contents(__DIR__.'/data/PUT.json'), true);
//        $this->client->request('PUT', sprintf('/api/reports/%s/edit', $id), $data);
//        $response = $this->client->getResponse();
//        $this->assertSame(Codes::HTTP_OK, $response->getStatusCode());
//        $responseData = json_decode($response->getContent(), true);
//        $this->assertTrue(isset($responseData['report']));
//        $this->assertEquals($data['name'], $responseData['report']['name']);
//        $this->assertEquals($data['source'], $responseData['report']['source']);
//        $this->assertEquals($data['scheduleUnit'], $responseData['report']['scheduleUnit']);
//        $this->assertEquals($data['toAddress'], $responseData['report']['toAddress']);
//        $this->assertEquals($data['scheduleDay'], $responseData['report']['scheduleDay']);
//        $this->assertEmpty($responseData['report']['filters']);
//
//        // DELETE a report
//        $this->client->request('DELETE', sprintf('/api/reports/%s/delete', $id), $data);
//        $response = $this->client->getResponse();
//        $this->assertSame(Codes::HTTP_OK, $response->getStatusCode());
//        $this->assertTrue(isset($responseData['report']));
//        $this->assertEquals($data['name'], $responseData['report']['name']);
//        $this->client->request('GET', sprintf('/api/reports/%s', $id), $data);
//        $response = $this->client->getResponse();
//        $this->assertSame(Codes::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
