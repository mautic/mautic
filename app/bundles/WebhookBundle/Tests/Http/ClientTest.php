<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Tests\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Http\RequestFactory;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testPost()
    {
        $method  = 'POST';
        $url     = 'url';
        $payload = ['payload'];
        $siteUrl = 'siteUrl';
        $headers = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $siteUrl,
        ];
        $request  = new Request($method, $url); // It doesn't matter what args are used here
        $response = new Response(); // here too

        $parametersMock     = $this->createMock(CoreParametersHelper::class);
        $requestFactoryMock = $this->createMock(RequestFactory::class);
        $httpClientMock     = $this->createMock(GuzzleClient::class);

        $parametersMock->expects($this->once())
            ->method('getParameter')
            ->with('site_url')
            ->willReturn($siteUrl);

        $requestFactoryMock->expects($this->once())
            ->method('create')
            ->with($method, $url, $headers, $payload)
            ->willReturn($request);

        $httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $client = new Client($parametersMock, $requestFactoryMock, $httpClientMock);

        $this->assertEquals($response, $client->post($url, $payload));
    }
}
