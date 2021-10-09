<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Controller;

use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PipedriveControllerTest extends PipedriveTest
{
    public function testWithoutIntegration()
    {
        $this->makeRequest('POST', '');

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'Integration turned off');
    }

    public function testWithTurnedOffIntegration()
    {
        $this->installPipedriveIntegration(false);

        $this->makeRequest('POST', '');

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'Integration turned off');
    }

    public function testWithoutAuthorizationInRequest()
    {
        $this->installPipedriveIntegration(true);

        $this->makeRequest('POST', '', false);

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testWithoutAuthorizationInIntegration()
    {
        $this->installPipedriveIntegration(true, [], [], [], false);

        $this->makeRequest('POST', '');

        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
    }
}
