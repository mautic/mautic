<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Controller;

use Mautic\ConfigBundle\Form\Helper\RestrictionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    private const SUBDOMAIN_URL = 'subdomain_url.com';

    private string $prefix;

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['config_allowed_parameters'] = [
            'kernel.project_dir',
        ];

        $this->configParams['locale']        = 'en_US';
        $this->configParams['subdomain_url'] = self::SUBDOMAIN_URL;

        parent::setUp();

        if ('testRestrictedAssetFieldIsNotRenderedInConfigForm' === $this->name()) {
            $translator        = self::getContainer()->get(TranslatorInterface::class);
            $restrictionHelper = new RestrictionHelper(
                $translator,
                ['upload_dir'],
                RestrictionHelper::MODE_REMOVE
            );
            self::getContainer()->set(RestrictionHelper::class, $restrictionHelper);
            self::getContainer()->set('mautic.config.form.restriction_helper', $restrictionHelper);
        }

        $this->prefix = MAUTIC_TABLE_PREFIX;
    }

    public function testValuesAreEscapedProperly(): void
    {
        $trackIps        = "%ip1%\n%ip2%\n%kernel.project_dir%";
        $googleAnalytics = 'reveal pass: %mautic.db_password%';

        $this->submitConfigPage('coreconfig', [
            'config[coreconfig][site_url]'         => 'https://mautic-community.local', // required
            'config[coreconfig][do_not_track_ips]' => $trackIps,
        ]);
        $this->submitConfigPage('pageconfig', [
            'config[pageconfig][google_analytics]' => $googleAnalytics,
        ]);

        // Check values are escaped properly in the config file
        $configParameters = $this->getConfigParameters();
        $this->assertArrayHasKey('do_not_track_ips', $configParameters);
        $this->assertSame([
            $this->escape('%ip1%'),
            $this->escape('%ip2%'),
            '%kernel.project_dir%',
        ], $configParameters['do_not_track_ips']);
        $this->assertArrayHasKey('google_analytics', $configParameters);
        $this->assertSame($this->escape($googleAnalytics), $configParameters['google_analytics']);
        // Check values are unescaped properly in the edit form
        $this->assertSame($trackIps, $this->getConfigPageForm('coreconfig')['config[coreconfig][do_not_track_ips]']->getValue());
        $this->assertSame($googleAnalytics, $this->getConfigPageForm('pageconfig')['config[pageconfig][google_analytics]']->getValue());
    }

    public function testConfigIndexRedirectsToTheGeneralSettings(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $this->assertSame('/s/config/edit/coreconfig', $this->client->getRequest()->getPathInfo());
        $this->assertSame('page', $crawler->filter('.list-group-tabs a[href="/s/config/edit/coreconfig"]')->attr('aria-current'));
    }

    public function testConfigPageRendersOnlyItsOwnFields(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/pageconfig');
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('#config_pageconfig_google_analytics'));
        $this->assertCount(0, $crawler->filter('#config_coreconfig_site_url'));
        $this->assertCount(0, $crawler->filter('#config_emailconfig_mailer_from_email'));

        // The navigation still links to every configuration page
        $this->assertCount(1, $crawler->filter('.list-group-tabs a[href="/s/config/edit/coreconfig"]'));
        $this->assertCount(1, $crawler->filter('.list-group-tabs a[href="/s/config/edit/emailconfig"]'));
    }

    public function testGenericActionRouteStillOpensTheConfiguration(): void
    {
        // The admin menu and plugins link to the configuration through the generic action route
        $url = self::getContainer()->get(RouterInterface::class)->generate('mautic_config_action', ['objectAction' => 'edit', 'objectId' => 'emailconfig']);

        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
        $this->assertSame('/s/config/edit/emailconfig', $this->client->getRequest()->getPathInfo());
    }

    public function testLegacyTabQueryRedirectsToThePage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=trackingconfig');
        $this->assertResponseIsSuccessful();
        $this->assertSame('/s/config/edit/trackingconfig', $this->client->getRequest()->getPathInfo());
    }

    public function testUnknownConfigPageReturnsNotFound(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/config/edit/doesnotexist');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostWithoutPageDoesNotSaveAnything(): void
    {
        $form   = $this->getConfigPageForm('pageconfig');
        $values = $form->getPhpValues();
        $values['config']['pageconfig']['google_analytics'] = 'ga-snippet';

        $this->client->request(Request::METHOD_POST, '/s/config/edit', $values);
        $this->assertResponseIsSuccessful();

        $this->assertSame('/s/config/edit/coreconfig', $this->client->getRequest()->getPathInfo());
        $this->assertArrayNotHasKey('google_analytics', $this->getConfigParameters());
    }

    public function testSavingOnePageKeepsTheValuesOfOtherPages(): void
    {
        $googleAnalytics = 'ga-snippet';

        $this->submitConfigPage('pageconfig', [
            'config[pageconfig][google_analytics]' => $googleAnalytics,
        ]);
        $this->submitConfigPage('coreconfig', [
            'config[coreconfig][site_url]' => 'https://mautic-community.local', // required
        ]);

        $this->assertSame('/s/config/edit/coreconfig', $this->client->getRequest()->getPathInfo());
        $this->assertSame($googleAnalytics, $this->getConfigParameters()['google_analytics']);
    }

    public function testConfigPageWithErrorsShowsAlert(): void
    {
        $form = $this->getConfigPageForm('coreconfig', 'apply');
        $form->setValues(['config[coreconfig][site_url]' => '']);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertSame('/s/config/edit/coreconfig', $this->client->getRequest()->getPathInfo());
        $this->assertStringContainsString(
            'Some settings on this page are not valid.',
            $crawler->filter('form[name="config"] .alert-danger')->text()
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function submitConfigPage(string $page, array $values, string $button = 'apply'): Crawler
    {
        $form = $this->getConfigPageForm($page, $button);
        $form->setValues($values);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Check for a flash error
        $message = $crawler->filterXPath("//div[@id='flashes']//span")->count()
            ? $crawler->filterXPath("//div[@id='flashes']//span")->first()->text()
            : '';
        $this->assertStringNotContainsString('Could not save updated configuration:', (string) $this->client->getResponse()->getContent(), $message);

        return $crawler;
    }

    private function getConfigPageForm(string $page, string $button = 'save'): Form
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/'.$page);
        $this->assertResponseIsSuccessful();

        return $crawler->selectButton('config[buttons]['.$button.']')->form();
    }

    private function getConfigPath(): string
    {
        /** @var \AppKernel $kernel */
        $kernel = self::getContainer()->get(KernelInterface::class);

        return $kernel->getLocalConfigFile();
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigParameters(): array
    {
        $parameters = [];
        include $this->getConfigPath();

        return $parameters;
    }

    private function escape(string $value): string
    {
        return str_replace('%', '%%', $value);
    }

    public function testConfigNotFoundPageConfiguration(): void
    {
        // insert published record
        $this->connection->insert($this->prefix.'pages', [
            'is_published' => 1,
            'date_added'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'title'        => 'page1',
            'alias'        => 'page1',
            'template'     => 'blank',
            'custom_html'  => 'Page1 Test Html',
            'hits'         => 0,
            'unique_hits'  => 0,
            'variant_hits' => 0,
            'revision'     => 0,
            'lang'         => 'en',
        ]);
        $page1 = $this->connection->lastInsertId();

        // insert unpublished record
        $this->connection->insert($this->prefix.'pages', [
            'is_published' => 0,
            'date_added'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'title'        => 'page2',
            'alias'        => 'page2',
            'template'     => 'blank',
            'custom_html'  => 'Page2 Test Html',
            'hits'         => 0,
            'unique_hits'  => 0,
            'variant_hits' => 0,
            'revision'     => 0,
            'lang'         => 'en',
        ]);
        $this->connection->lastInsertId();

        // insert published record
        $this->connection->insert($this->prefix.'pages', [
            'is_published' => 1,
            'date_added'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'title'        => 'page3',
            'alias'        => 'page3',
            'template'     => 'blank',
            'custom_html'  => 'Page3 Test Html',
            'hits'         => 0,
            'unique_hits'  => 0,
            'variant_hits' => 0,
            'revision'     => 0,
            'lang'         => 'en',
        ]);
        $page3 = $this->connection->lastInsertId();

        $form = $this->getConfigPageForm('coreconfig');

        // Fetch available option for 404_page field
        $notFoundPageField = $form['config[coreconfig][404_page]'];
        $this->assertInstanceOf(ChoiceFormField::class, $notFoundPageField);
        $availableOptions = $notFoundPageField->availableOptionValues();

        // page 2 should not be available in option list because it is unpublished
        $this->assertEquals(['', $page1, $page3], $availableOptions);

        // page 3 for 404_page
        $this->submitConfigPage('coreconfig', [
            'config[coreconfig][site_url]' => 'https://mautic-community.local', // required
            'config[coreconfig][404_page]' => $page3,
        ]);

        $this->assertEquals($page3, $this->getConfigPageForm('coreconfig')['config[coreconfig][404_page]']->getValue());
        // re-create the Symfony client to make config changes applied
        $this->setUpSymfony($this->configParams);

        // Request not found url page3 page content should be rendered
        $crawler = $this->client->request(Request::METHOD_GET, '/notfoundurlblablabla');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringContainsString('Page3 Test Html', $crawler->text());
    }

    public function testConfigNotificationConfiguration(): void
    {
        $send_notification_to_author           = '0';
        $campaign_notification_email_addresses = 'a@test.com, b@test.com';
        $webhook_notification_email_addresses  = 'a@webhook.com, b@webhook.com';

        $this->submitConfigPage('notification_config', [
            'config[notification_config][campaign_send_notification_to_author]'  => $send_notification_to_author,
            'config[notification_config][campaign_notification_email_addresses]' => $campaign_notification_email_addresses,
            'config[notification_config][webhook_send_notification_to_author]'   => $send_notification_to_author,
            'config[notification_config][webhook_notification_email_addresses]'  => $webhook_notification_email_addresses,
        ]);

        $form = $this->getConfigPageForm('notification_config');

        $this->assertSame($send_notification_to_author, $form['config[notification_config][campaign_send_notification_to_author]']->getValue());
        $this->assertSame($campaign_notification_email_addresses, $form['config[notification_config][campaign_notification_email_addresses]']->getValue());
        $this->assertSame($send_notification_to_author, $form['config[notification_config][webhook_send_notification_to_author]']->getValue());
        $this->assertSame($webhook_notification_email_addresses, $form['config[notification_config][webhook_notification_email_addresses]']->getValue());
    }

    public function testContactExportAdminNotificationsConfigDefaultsToEnabledAndCanBeDisabled(): void
    {
        $this->assertSame('1', $this->getConfigPageForm('leadconfig')['config[leadconfig][contact_export_notify_admins]']->getValue());

        $this->submitConfigPage('leadconfig', [
            'config[leadconfig][contact_columns]'              => ['name', 'email', 'id'],
            'config[leadconfig][contact_export_notify_admins]' => '0',
        ]);

        $configParameters = $this->getConfigParameters();
        $this->assertArrayHasKey('contact_export_notify_admins', $configParameters);
        $this->assertFalse((bool) $configParameters['contact_export_notify_admins']);

        $this->assertSame('0', $this->getConfigPageForm('leadconfig')['config[leadconfig][contact_export_notify_admins]']->getValue());
    }

    public function testRestrictedAssetFieldIsNotRenderedInConfigForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/assetconfig');
        $this->assertResponseIsSuccessful();

        // Restricted explicitly in test setUp().
        $this->assertCount(0, $crawler->filter('#config_assetconfig_upload_dir'));
        $this->assertCount(1, $crawler->filter('#config_assetconfig_max_size'));
        $this->assertCount(1, $crawler->filter('#config_assetconfig_allowed_extensions'));
    }

    public function testUserAndSystemLocale(): void
    {
        // 1. Change user locale in account - should change _locale session
        $accountCrawler = $this->client->request(Request::METHOD_GET, '/s/account');
        $this->assertResponseIsSuccessful();
        $accountSaveButton = $accountCrawler->selectButton('user[buttons][save]');
        $accountForm       = $accountSaveButton->form();
        $accountForm->setValues(
            [
                'user[locale]' => 'en_US',
            ]
        );
        $this->client->submit($accountForm);
        $this->assertResponseIsSuccessful();
        $this->assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

        // 2. Change system locale in configuration - should not change _locale session
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit/coreconfig');
        $configSaveButton = $configCrawler->selectButton('config[buttons][save]');
        $configForm       = $configSaveButton->form();
        $configForm->setValues(
            [
                'config[coreconfig][locale]'   => 'en_US',
                'config[coreconfig][site_url]' => 'https://mautic-cloud.local', // required
            ]
        );
        $this->client->submit($configForm);
        $this->assertResponseIsSuccessful();
        $this->assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

        // 3. Change user locale to system default in account - should change _locale session to system default
        $accountCrawler    = $this->client->request(Request::METHOD_GET, '/s/account');
        $accountSaveButton = $accountCrawler->selectButton('user[buttons][save]');
        $accountForm       = $accountSaveButton->form();
        $accountForm->setValues(
            [
                'user[locale]' => '',
            ]
        );
        $this->client->submit($accountForm);
        $this->assertResponseIsSuccessful();
        $this->assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

        // 2. Change system locale in configuration to en_US - should change _locale session
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit/coreconfig');
        $configSaveButton = $configCrawler->selectButton('config[buttons][save]');
        $configForm       = $configSaveButton->form();
        $configForm->setValues(
            [
                'config[coreconfig][locale]'   => 'en_US',
                'config[coreconfig][site_url]' => 'https://mautic-cloud.local', // required
            ]
        );
        $this->client->submit($configForm);
        $this->assertResponseIsSuccessful();
        $this->assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));
    }

    public function testSSOSettingEntityId(): void
    {
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit/userconfig');
        $configSaveButton = $configCrawler->selectButton('config[buttons][apply]');
        $configForm       = $configSaveButton->form();

        /** @var ChoiceFormField $entityIdField */
        $entityIdField    = $configForm['config[userconfig][saml_idp_entity_id]'];
        $availableOptions = $entityIdField->availableOptionValues();
        $this->assertCount(3, $availableOptions);
        $configForm->setValues(
            [
                'config[userconfig][saml_idp_entity_id]' => $availableOptions[1],
            ]
        );
        $this->client->submit($configForm);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($availableOptions[1], $configForm['config[userconfig][saml_idp_entity_id]']->getValue());
    }
}
