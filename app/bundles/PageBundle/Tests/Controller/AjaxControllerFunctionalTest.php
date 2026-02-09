<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGetBuilderTokensAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=page:getBuilderTokens');
        Assert::assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('tokens', $response);
        Assert::assertArrayHasKey('{contactfield=email}', $response['tokens']);
        Assert::assertArrayHasKey('{ownerfield=email}', $response['tokens']);
        Assert::assertArrayHasKey('{langbar}', $response['tokens']);
    }

    public function testGetBuilderTokensActionWithTokenKeyFilter(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=page:getBuilderTokens&query='.urlencode('{contactfield'));
        Assert::assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('tokens', $response);
        Assert::assertArrayHasKey('{contactfield=email}', $response['tokens']);
        Assert::assertArrayNotHasKey('{langbar}', $response['tokens']);
    }

    public function testGetBuilderTokensActionWithLabelFilter(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=page:getBuilderTokens&query='.urlencode('{@This page'));
        Assert::assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('tokens', $response);
        Assert::assertArrayHasKey('{langbar}', $response['tokens']);
        Assert::assertArrayNotHasKey('{contactfield=email}', $response['tokens']);
    }
}
