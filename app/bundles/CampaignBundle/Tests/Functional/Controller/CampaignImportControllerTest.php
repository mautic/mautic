<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CampaignImportControllerTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNewAction(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/s/campaign/import/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('campaignImport', $response->getContent());
    }

    public function testCancelAction(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Start the session by making a request
        $this->client->request(Request::METHOD_GET, '/s/campaign/import/new');

        $this->client->request(Request::METHOD_GET, '/s/campaign/import/cancel');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testProgressAction(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Start the session by making a request
        $this->client->request(Request::METHOD_GET, '/s/campaign/import/new');

        $this->client->request(Request::METHOD_GET, '/s/campaign/import/progress');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('campaignImport', $response->getContent());
    }
}
