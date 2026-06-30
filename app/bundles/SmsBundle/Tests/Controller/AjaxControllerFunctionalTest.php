<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGetBuilderTokensAction(): void
    {
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=sms:getBuilderTokens');
        self::assertResponseIsSuccessful();
        $tokens = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('tokens', $tokens);
        $this->assertArrayHasKey('{contactfield=email}', $tokens['tokens']);
        $this->assertArrayHasKey('{ownerfield=email}', $tokens['tokens']);
    }
}
