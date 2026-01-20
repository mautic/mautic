<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PluginControllerTest extends MauticMysqlTestCase
{
    public function testReturnPluginVersion(): void
    {
        $this->testSymfonyCommand('mautic:plugins:install');
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/plugins/info/MauticFocusBundle');

        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());

        $content = $response->getContent();
        Assert::assertJson($content);

        $data = json_decode($content, true);
        Assert::assertArrayHasKey('pluginVersion', $data);
        Assert::assertSame('1.0', $data['pluginVersion']);
    }
}
