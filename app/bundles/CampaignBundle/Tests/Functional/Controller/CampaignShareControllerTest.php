<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Service\CampaignShareService;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CampaignShareControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

    private const SHARE_ROUTE       = '/s/campaigns/share/';

    private const CAMPAIGN_NAME     = 'Share Test Campaign';

    private const TEST_DESCRIPTION  = 'This is a test description for the campaign share form. This is a test description for the campaign share form. This is a test description for the campaign share form. ';

    private Campaign $campaign;

    private function setupShareTestData(int $exportPermission = 1024): User
    {
        $nonAdminUser = $this->createUserWithPermission([
            'user-name'  => 'share-test-user',
            'email'      => 'share-test@mautic-test.com',
            'first-name' => 'Share',
            'last-name'  => 'Tester',
            'role'       => [
                'name'        => 'perm_share_test',
                'permissions' => [
                    'lead:leads'             => 2,
                    'campaign:campaigns'     => 2,
                    'campaign:export:enable' => $exportPermission,
                ],
            ],
        ]);

        $this->campaign = new Campaign();
        $this->campaign->setName(self::CAMPAIGN_NAME);
        $this->campaign->setDescription('A test campaign for share functionality with enough description text to pass validation. Adding more text here to meet the minimum requirements.');
        $this->campaign->setIsPublished(true);
        $this->em->persist($this->campaign);

        $this->em->flush();

        return $nonAdminUser;
    }

    public function testShareFormLoads(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertStringContainsString(self::CAMPAIGN_NAME, (string) $content);
    }

    public function testShareFormAccessDenied(): void
    {
        $nonAdminUser = $this->setupShareTestData(0);
        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testShareCampaignNotFound(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.'999999');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testShareDownloadReturnsZip(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'                => self::CAMPAIGN_NAME,
            'campaign_share[vendorName]'           => 'acme',
            'campaign_share[version]'              => '1.0.0',
            'campaign_share[headline]'             => 'Test Headline For Campaign',
            'campaign_share[description]'          => self::TEST_DESCRIPTION,
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.zip', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * Regression test: buildShareZip() used to write every archive to the same fixed path
     * (sys_get_temp_dir().'/entity_data.zip'). Two overlapping share/download requests would
     * then race on that single file — one request could delete or overwrite the other's
     * archive mid-flight. Each call must now get its own private temp file.
     */
    public function testConcurrentShareBuildsDoNotClobberEachOther(): void
    {
        $shareService = self::getContainer()->get(CampaignShareService::class);
        $this->assertInstanceOf(CampaignShareService::class, $shareService);

        $campaignOne = new Campaign();
        $campaignOne->setName('Concurrent Campaign One');
        $campaignTwo = new Campaign();
        $campaignTwo->setName('Concurrent Campaign Two');
        $this->em->persist($campaignOne);
        $this->em->persist($campaignTwo);
        $this->em->flush();

        // Simulate two requests interleaving: build the first archive, then build the
        // second before the first has been consumed.
        $firstZipPath  = $shareService->buildShareZip($campaignOne, ['campaign' => [['id' => $campaignOne->getId(), 'name' => 'One']]], [], ['title' => 'One', 'vendorName' => 'acme', 'version' => '1.0.0']);
        $secondZipPath = $shareService->buildShareZip($campaignTwo, ['campaign' => [['id' => $campaignTwo->getId(), 'name' => 'Two']]], [], ['title' => 'Two', 'vendorName' => 'acme', 'version' => '1.0.0']);

        try {
            $this->assertNotSame($firstZipPath, $secondZipPath, 'Each share build must use its own file, not a shared path.');
            $this->assertFileExists($firstZipPath, 'Building the second archive must not delete the first.');
            $this->assertFileExists($secondZipPath);

            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($firstZipPath));
            $composerJson = json_decode((string) $zip->getFromName('composer.json'), true);
            $zip->close();
            $this->assertStringContainsString('one', strtolower((string) $composerJson['name']));
        } finally {
            @unlink($firstZipPath);
            @unlink($secondZipPath);
            @rmdir(\dirname($firstZipPath));
            @rmdir(\dirname($secondZipPath));
        }
    }

    public function testSharePublishRedirectsToMarketplace(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('campaign_share[publish]')->form();
        $form->setValues([
            'campaign_share[title]'                => self::CAMPAIGN_NAME,
            'campaign_share[vendorName]'           => 'acme',
            'campaign_share[version]'              => '1.0.0',
            'campaign_share[headline]'             => 'Test Headline For Campaign',
            'campaign_share[description]'          => self::TEST_DESCRIPTION,
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $content  = $response->getContent();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Publishing to Marketplace', (string) $content);
        // The publish flow should hand the marketplace a token URL pointing back at the share
        // download endpoint — not a Mautic Asset URL — so the marketplace can fetch the ZIP
        // without depending on the Asset table. The URL is rendered into the
        // `data-archive-url` attribute via Twig's `html_attr` escaper, which turns `/` into `&#x2F;`.
        $this->assertMatchesRegularExpression('#campaign-share&\#x2F;[a-f0-9]{32}#', $content);
    }

    public function testShareDownloadEndpointRejectsUnknownToken(): void
    {
        $this->client->request(Request::METHOD_GET, '/campaign-share/'.str_repeat('a', 32));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShareDownloadEndpointRejectsMalformedToken(): void
    {
        // Routing requires 32 hex chars — anything else must not even reach the controller.
        $this->client->request(Request::METHOD_GET, '/s/campaign-share/not-a-real-token');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShareDownloadServesStoredZip(): void
    {
        $shareService = self::getContainer()->get(CampaignShareService::class);
        $this->assertInstanceOf(CampaignShareService::class, $shareService);
        $shareDir = $shareService->getShareDir();

        if (!is_dir($shareDir)) {
            mkdir($shareDir, 0775, true);
        }

        $token   = bin2hex(random_bytes(16));
        $zipPath = $shareDir.'/'.$token.'.zip';

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE));
        $zip->addFromString('composer.json', '{"name":"acme/test","type":"mautic-resource","version":"1.0.0"}');
        $zip->close();
        $this->assertFileExists($zipPath);

        $this->client->request(Request::METHOD_GET, '/campaign-share/'.$token);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('campaign-share.zip', (string) $response->headers->get('Content-Disposition'));

        // Test cleanup — the controller asks the kernel to delete the file after send, but
        // that hook does not always fire in WebTestCase, so unlink defensively here.
        @unlink($zipPath);
    }

    public function testShareDownloadReturnsGoneForExpiredFile(): void
    {
        $shareService = self::getContainer()->get(CampaignShareService::class);
        $this->assertInstanceOf(CampaignShareService::class, $shareService);
        $shareDir = $shareService->getShareDir();

        if (!is_dir($shareDir)) {
            mkdir($shareDir, 0775, true);
        }

        $token   = bin2hex(random_bytes(16));
        $zipPath = $shareDir.'/'.$token.'.zip';
        file_put_contents($zipPath, 'PK-placeholder');

        // Backdate the file past the TTL so the controller treats it as expired.
        $staleTime = time() - (CampaignShareService::SHARE_TTL_SECONDS + 60);
        touch($zipPath, $staleTime);

        $this->client->request(Request::METHOD_GET, '/campaign-share/'.$token);

        $this->assertEquals(Response::HTTP_GONE, $this->client->getResponse()->getStatusCode());
        // Expired files are unlinked on access.
        $this->assertFileDoesNotExist($zipPath);
    }

    public function testShareDownloadPacksEmailBuilderImages(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        // A builder image on disk, referenced from the campaign's email the same way
        // the email builder stores it (absolute URL under media/images).
        $pathsHelper = self::getContainer()->get(PathsHelper::class);
        $imageDir    = rtrim($pathsHelper->getImagePath(), '/');
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0775, true);
        }
        $fileName  = 'share_asset_'.bin2hex(random_bytes(6)).'.png';
        $imagePath = $imageDir.'/'.$fileName;
        file_put_contents($imagePath, 'fake-png-bytes');

        $email = $this->createEmail('Share Asset Email');
        $email->setCustomHtml('<img src="https://origin.example.com/media/images/'.$fileName.'">');
        // Flush before building the event so the email has an ID; entities use deferred
        // explicit change tracking, so channel data must be set before the initial insert
        // (a later set + flush without re-persisting would be silently dropped).
        $this->em->flush();

        $event = $this->createEvent('Send share email', $this->campaign, 'email.send', 'action', ['email' => $email->getId()]);
        $event->setChannel('email');
        $event->setChannelId($email->getId());
        $this->em->flush();

        // The test client shares this EntityManager, so without clearing it the export
        // would see the in-memory Campaign whose events collection was never populated
        // (the event only set the owning side). Clearing forces a fresh DB load, which
        // is what a real request would do.
        $campaignId = $this->campaign->getId();
        $this->em->clear();

        try {
            $crawler = $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$campaignId);

            $form    = $crawler->selectButton('campaign_share[download]')->form();
            $form->setValues([
                'campaign_share[title]'             => self::CAMPAIGN_NAME,
                'campaign_share[vendorName]'        => 'acme',
                'campaign_share[version]'           => '1.0.0',
                'campaign_share[headline]'          => 'Test Headline For Campaign',
                'campaign_share[description]'       => self::TEST_DESCRIPTION,
                'campaign_share[worksWithVersions]' => ['5.0'],
            ]);

            $this->client->submit($form);
            $response = $this->client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertSame('application/zip', $response->headers->get('Content-Type'));

            ob_start();
            $response->sendContent();
            $zipContents = (string) ob_get_clean();

            $zipPath = tempnam(sys_get_temp_dir(), 'share_zip_');
            file_put_contents($zipPath, $zipContents);

            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($zipPath));
            $this->assertNotFalse(
                $zip->locateName('assets/'.$fileName),
                'The share archive must contain the email builder image under assets/.'
            );
            $zip->close();
            @unlink($zipPath);
        } finally {
            @unlink($imagePath);
        }
    }

    public function testFailedValidationKeepsUploadedBannerAndPacksItOnResubmit(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());

        // Submit with a banner but a blank vendor name, so validation fails.
        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'             => self::CAMPAIGN_NAME,
            'campaign_share[vendorName]'        => '',
            'campaign_share[version]'           => '1.0.0',
            'campaign_share[headline]'          => 'Test Headline For Campaign',
            'campaign_share[description]'       => self::TEST_DESCRIPTION,
            'campaign_share[worksWithVersions]' => ['5.0'],
        ]);
        $pngPath     = $this->createTempPng();
        $bannerField = $form['campaign_share[bannerImage]'];
        $this->assertInstanceOf(FileFormField::class, $bannerField);
        $bannerField->upload($pngPath);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotSame('application/zip', $response->headers->get('Content-Type'));

        // The re-rendered form must carry the stash token and show the kept filename.
        $stashToken = (string) $crawler->filter('input[name="campaign_share[bannerImageStash]"]')->attr('value');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $stashToken);
        $this->assertStringContainsString(basename($pngPath), (string) $response->getContent());

        // Fix the vendor name and re-submit WITHOUT selecting the file again.
        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'             => self::CAMPAIGN_NAME,
            'campaign_share[vendorName]'        => 'acme',
            'campaign_share[version]'           => '1.0.0',
            'campaign_share[headline]'          => 'Test Headline For Campaign',
            'campaign_share[description]'       => self::TEST_DESCRIPTION,
            'campaign_share[worksWithVersions]' => ['5.0'],
        ]);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $zipContents = (string) ob_get_clean();

        $zipPath = tempnam(sys_get_temp_dir(), 'share_zip_');
        file_put_contents($zipPath, $zipContents);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('assets/banner.png'), 'The stashed banner must be packed into the downloaded ZIP.');
        $zip->close();
        @unlink($zipPath);
    }

    private function createTempPng(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'share_banner_');
        rename($path, $path .= '.png');
        // 1x1 transparent PNG so the File constraint's mime sniffing sees a real image.
        file_put_contents($path, (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true));

        return $path;
    }

    public function testShareFormValidationRejectsInvalidVersion(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, self::SHARE_ROUTE.$this->campaign->getId());

        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'                => self::CAMPAIGN_NAME,
            'campaign_share[vendorName]'           => 'acme',
            'campaign_share[version]'              => 'invalid-version',
            'campaign_share[headline]'             => 'Test Headline',
            'campaign_share[description]'          => self::TEST_DESCRIPTION,
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $this->client->submit($form);

        // Form should re-render with validation errors, not produce a ZIP
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotSame('application/zip', $response->headers->get('Content-Type'));
    }
}
