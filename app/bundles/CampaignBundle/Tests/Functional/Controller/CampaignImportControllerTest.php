<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CampaignImportControllerTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        $this->useCleanupRollback = false;
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

    public function testUndoAction(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Make a dummy request to initialize session
        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();

        // Simulate import summary with NEW entities to trigger undo
        $session->set('mautic.campaign.import.summary', [
            [
                EntityImportEvent::NEW => [
                    Campaign::ENTITY_NAME => [
                        'ids' => [101, 102],
                    ],
                ],
            ],
        ]);
        $session->save();

        $this->client->request('GET', '/s/campaign/import/undo');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('The last import has been undone successfully.', $response->getContent());
    }

    public function testUndoActionWithoutUndoData(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Dummy request to initialize session
        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();

        // Simulate import summary with only UPDATE (no NEW data)
        $session->set('mautic.campaign.import.summary', [
            [
                EntityImportEvent::UPDATE => [
                    Campaign::ENTITY_NAME => [
                        'ids' => [201, 202],
                    ],
                ],
            ],
        ]);
        $session->save();

        $this->client->request('GET', '/s/campaign/import/undo');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('No data found for import undo.', $response->getContent());
    }

    public function testProgressActionAnalyzeDataErrors(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();
        $session->set('mautic.campaign.import.step', 2);
        $session->set('mautic.campaign.import.file', __DIR__.'/Fixtures/empty.zip');
        $session->save();

        $importHelper = $this->createMock(ImportHelper::class);
        $importHelper->method('readZipFile')->willReturn([]);
        static::getContainer()->set(ImportHelper::class, $importHelper);

        $this->client->request('GET', '/s/campaign/import/progress');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testProgressActionAnalyzeDataValid(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();

        $fixturesDir = __DIR__.'/Fixtures';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0775, true);
        }

        $fakePath = $fixturesDir.'/fake.zip';
        file_put_contents($fakePath, 'dummy zip content');

        $session->set('mautic.campaign.import.step', 2);
        $session->set('mautic.campaign.import.file', $fakePath);
        $session->save();

        $importHelper = $this->createMock(ImportHelper::class);
        $importHelper->method('readZipFile')->willReturn(FixtureHelper::getPayload());
        static::getContainer()->set(ImportHelper::class, $importHelper);

        $this->client->request('GET', '/s/campaign/import/progress');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        @unlink($fakePath);
    }

    public function testProgressActionImportEmptyFile(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();
        $session->set('mautic.campaign.import.step', 3);
        $session->set('mautic.campaign.import.file', __DIR__.'/Fixtures/empty.zip');
        $session->save();

        $importHelper = $this->createMock(ImportHelper::class);
        $importHelper->method('readZipFile')->willReturn([]);
        static::getContainer()->set(ImportHelper::class, $importHelper);

        $this->client->request('GET', '/s/campaign/import/progress');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testProgressActionImportValidData(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();

        $fixturesDir = __DIR__.'/Fixtures';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0775, true);
        }

        $fakePath = $fixturesDir.'/fake.zip';
        file_put_contents($fakePath, 'dummy zip content');

        $session->set('mautic.campaign.import.step', 3);
        $session->set('mautic.campaign.import.file', $fakePath);
        $session->save();

        $importHelper = $this->createMock(ImportHelper::class);
        $importHelper->method('readZipFile')->willReturn(FixtureHelper::getPayload());
        static::getContainer()->set(ImportHelper::class, $importHelper);

        $this->client->request('GET', '/s/campaign/import/progress');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        @unlink($fakePath);
    }

    public function testUploadActionWithValidFile(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $tmpFile = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmpFile, 'dummy zip content');

        $fileArray = [
            'tmp_name' => $tmpFile,
            'name'     => 'test.zip',
            'type'     => 'application/zip',
            'size'     => filesize($tmpFile),
            'error'    => UPLOAD_ERR_OK,
        ];

        $this->client->request(
            'POST',
            '/s/campaign/import/upload',
            ['campaign_import' => []], // POST data
            ['campaign_import' => ['campaignFile' => $fileArray]]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        @unlink($tmpFile);
    }
}
