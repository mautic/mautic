<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThemeControllerTest extends MauticMysqlTestCase
{
    public function testDeleteTheme(): void
    {
        $this->client->request(Request::METHOD_POST, 's/themes/batchDelete?ids=[%22aurora%22]');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('aurora is the default theme and therefore cannot be removed.', $this->client->getResponse()->getContent());
    }
}
