<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SamlControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->clientServer = [];
        parent::setUp();
        $this->client->followRedirects(false);
    }

    public function testLoginRedirectsToDiscoveryWhenIdpIsMissing(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/saml/login');

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        self::assertResponseRedirects('/saml/discovery');
    }
}
