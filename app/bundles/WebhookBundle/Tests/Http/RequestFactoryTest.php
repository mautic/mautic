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

use Mautic\WebhookBundle\Http\RequestFactory;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $method        = 'METHOD';
        $url           = 'url';
        $headerTitle   = 'headerTitle';
        $headerContent = 'headerContent';
        $headers       = [$headerTitle => $headerContent];
        $payload       = ['payload' => 'payload'];

        $factory = new RequestFactory();
        $request = $factory->create($method, $url, $headers, $payload);

        $this->assertSame($method, $request->getMethod());
        $this->assertSame($url, $request->getUri()->getPath());
        $this->assertSame([$headerTitle => [0 => $headerContent]], $request->getHeaders());
        $this->assertSame(json_encode($payload), $request->getBody()->getContents());
    }
}
