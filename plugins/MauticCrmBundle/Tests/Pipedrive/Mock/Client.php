<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Mock;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class Client extends GuzzleClient
{
    public function request($method, $uri = '', array $options = [])
    {
        //it's hack, there is no option to pass information using class variable in Mautic...
        $GLOBALS['requests'][$method.'/'.$uri][] = $options;

        return new Response(200, [], PipedriveTest::getData($uri));
    }
}
