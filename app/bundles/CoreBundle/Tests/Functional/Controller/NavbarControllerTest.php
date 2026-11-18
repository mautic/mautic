<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavbarControllerTest extends MauticMysqlTestCase
{
    public function testGlobalSearchButtonTargetsItsModal(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('button.search-button[data-bs-toggle="modal"][data-bs-target="#gsearchModal"]'));
        $this->assertCount(1, $crawler->filter('#gsearchModal.modal'));
    }
}
