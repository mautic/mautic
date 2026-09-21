<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests proving that persisted email defaults from config are applied
 * when creating emails via the API (EMAIL_PRE_SAVE subscriber).
 */
final class EmailApiDefaultsFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Disabled because testNewEmailViaApiAppliesConfiguredDefaults calls
     * setUpSymfony() mid-test to reboot the kernel with the actual page ID.
     */
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['email_default_utm_source']    = 'config-source';
        $this->configParams['email_default_utm_medium']    = 'config-medium';
        $this->configParams['email_default_utm_campaign']  = 'config-campaign';
        $this->configParams['email_default_utm_content']   = 'config-content';

        parent::setUp();
    }

    public function testNewEmailViaApiAppliesOnlyPersistedConfiguredDefaults(): void
    {
        $preferenceCenter = $this->createPreferenceCenterPage('API Default PC');
        $this->em->flush();

        $pageId = $preferenceCenter->getId();

        // Reboot kernel so CoreParametersHelper picks up the page ID.
        $this->setUpSymfony(array_merge($this->configParams, [
            'email_default_preference_center_id' => $pageId,
        ]));

        // Re-authenticate: setUpSymfony() destroys the previous client and its security token.
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $payload = [
            'name'       => 'API defaults test',
            'subject'    => 'Test subject',
            'customHtml' => '<h1>Hello</h1>',
        ];

        $this->client->request(Request::METHOD_POST, '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($clientResponse->getContent(), true)['email'];

        $this->assertSame('config-source', $response['utmTags']['utmSource']);
        $this->assertSame('config-medium', $response['utmTags']['utmMedium']);
        $this->assertSame('config-campaign', $response['utmTags']['utmCampaign']);
        $this->assertSame('config-content', $response['utmTags']['utmContent']);

        // Verify the preference center stays null so the global default can be resolved at runtime.
        $emailId    = $response['id'];
        $savedEmail = $this->em->find(Email::class, $emailId);
        $this->assertInstanceOf(Email::class, $savedEmail, 'Email must be persisted');
        $this->assertNotInstanceOf(Page::class, $savedEmail->getPreferenceCenter(), 'Preference center must remain null for runtime fallback');
    }

    public function testNewEmailViaApiDoesNotOverwriteExplicitValues(): void
    {
        // Verify that explicitly provided UTM values override config defaults
        $payload = [
            'name'       => 'API explicit values test',
            'subject'    => 'Test subject',
            'customHtml' => '<h1>Hello</h1>',
            'utmTags'    => [
                'utmSource'   => 'explicit-source',
                'utmMedium'   => 'explicit-medium',
                'utmCampaign' => 'explicit-campaign',
                'utmContent'  => 'explicit-content',
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($clientResponse->getContent(), true)['email'];

        $this->assertSame('explicit-source', $response['utmTags']['utmSource']);
        $this->assertSame('explicit-medium', $response['utmTags']['utmMedium']);
        $this->assertSame('explicit-campaign', $response['utmTags']['utmCampaign']);
        $this->assertSame('explicit-content', $response['utmTags']['utmContent']);
    }

    private function createPreferenceCenterPage(string $name): Page
    {
        $page = new Page();
        $page->setTitle($name);
        $page->setAlias(mb_strtolower(str_replace(' ', '-', $name)));
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml('<html><body>Preference Center</body></html>');
        $page->setIsPublished(true);
        $this->em->persist($page);

        return $page;
    }
}
