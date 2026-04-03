<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignShareControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

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
        $this->campaign->setName('Share Test Campaign');
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

        $this->client->request(Request::METHOD_GET, '/s/campaigns/share/'.$this->campaign->getId());

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertStringContainsString('Share Test Campaign', $content);
    }

    public function testShareFormAccessDenied(): void
    {
        $nonAdminUser = $this->setupShareTestData(0);
        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/share/'.$this->campaign->getId());

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testShareCampaignNotFound(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/share/999999');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testShareDownloadReturnsZip(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/campaigns/share/'.$this->campaign->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'                => 'Share Test Campaign',
            'campaign_share[version]'              => '1.0.0',
            'campaign_share[headline]'             => 'Test Headline For Campaign',
            'campaign_share[description]'          => str_repeat('This is a test description for the campaign share form. ', 3),
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.zip', $response->headers->get('Content-Disposition'));
    }

    public function testSharePublishRedirectsToMarketplace(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/campaigns/share/'.$this->campaign->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('campaign_share[publish]')->form();
        $form->setValues([
            'campaign_share[title]'                => 'Share Test Campaign',
            'campaign_share[version]'              => '1.0.0',
            'campaign_share[headline]'             => 'Test Headline For Campaign',
            'campaign_share[description]'          => str_repeat('This is a test description for the campaign share form. ', 3),
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $content  = $response->getContent();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Redirecting to Marketplace', $content);
    }

    public function testShareFormValidationRejectsInvalidVersion(): void
    {
        $nonAdminUser = $this->setupShareTestData(1024);
        $this->loginOtherUser($nonAdminUser);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/campaigns/share/'.$this->campaign->getId());

        $form = $crawler->selectButton('campaign_share[download]')->form();
        $form->setValues([
            'campaign_share[title]'                => 'Share Test Campaign',
            'campaign_share[version]'              => 'invalid-version',
            'campaign_share[headline]'             => 'Test Headline',
            'campaign_share[description]'          => str_repeat('This is a test description for the campaign share form. ', 3),
            'campaign_share[worksWithVersions]'    => ['5.0'],
        ]);

        $crawler = $this->client->submit($form);

        // Form should re-render with validation errors, not produce a ZIP
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotSame('application/zip', $response->headers->get('Content-Type'));
    }
}
