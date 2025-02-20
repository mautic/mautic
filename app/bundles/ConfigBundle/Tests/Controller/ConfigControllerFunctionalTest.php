<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigControllerFunctionalTest extends MauticMysqlTestCase
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

        $this->prefix = MAUTIC_TABLE_PREFIX;
    }

    public function testValuesAreEscapedProperly(): void
    {
        $trackIps        = "%ip1%\n%ip2%\n%kernel.project_dir%";
        $googleAnalytics = 'reveal pass: %mautic.db_password%';

        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        // Find save & close button
        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        $form->setValues(
            [
                'config[coreconfig][site_url]'         => 'https://mautic-community.local', // required
                'config[coreconfig][do_not_track_ips]' => $trackIps,
                'config[pageconfig][google_analytics]' => $googleAnalytics,
                'config[leadconfig][contact_columns]'  => ['name', 'email', 'id'],
            ]
        );

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Check for a flash error
        $response = $this->client->getResponse()->getContent();
        $message  = $crawler->filterXPath("//div[@id='flashes']//span")->count()
            ?
            $crawler->filterXPath("//div[@id='flashes']//span")->first()->text()
            :
            '';
        Assert::assertStringNotContainsString('Could not save updated configuration:', $response, $message);

        // Check values are escaped properly in the config file
        $configParameters = $this->getConfigParameters();
        Assert::assertArrayHasKey('do_not_track_ips', $configParameters);
        Assert::assertSame(
            [
                $this->escape('%ip1%'),
                $this->escape('%ip2%'),
                '%kernel.project_dir%',
            ],
            $configParameters['do_not_track_ips']
        );
        Assert::assertArrayHasKey('google_analytics', $configParameters);
        Assert::assertSame($this->escape($googleAnalytics), $configParameters['google_analytics']);
        // Check values are unescaped properly in the edit form
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        Assert::assertEquals($trackIps, $form['config[coreconfig][do_not_track_ips]']->getValue());
        Assert::assertEquals($googleAnalytics, $form['config[pageconfig][google_analytics]']->getValue());
    }

    private function getConfigPath(): string
    {
        return static::getContainer()->get('kernel')->getLocalConfigFile();
    }

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

        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');

        // Find save & close button
        $buttonCrawler  = $crawler->selectButton('config[buttons][save]');
        $form           = $buttonCrawler->form();

        // Fetch available option for 404_page field
        $availableOptions = $form['config[coreconfig][404_page]']->availableOptionValues();

        // page 2 should not be available in option list because it is unpublished
        $this->assertEquals(['', $page1, $page3], $availableOptions);

        // page 3 for 404_page
        $form->setValues(
            [
                'config[coreconfig][site_url]'        => 'https://mautic-community.local', // required
                'config[leadconfig][contact_columns]' => ['name', 'email', 'id'],
                'config[coreconfig][404_page]'        => $page3,
            ]
        );

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        Assert::assertEquals($page3, $form['config[coreconfig][404_page]']->getValue());
        // re-create the Symfony client to make config changes applied
        $this->setUpSymfony($this->configParams);

        // Request not found url page3 page content should be rendered
        $crawler = $this->client->request(Request::METHOD_GET, '/notfoundurlblablabla');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringContainsString('Page3 Test Html', $crawler->text());
    }

    public function testConfigNotificationConfiguration(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');

        $buttonCrawler  =  $crawler->selectButton('config[buttons][save]');
        $form           = $buttonCrawler->form();

        $send_notification_to_author           = '0';
        $campaign_notification_email_addresses = 'a@test.com, b@test.com';
        $webhook_notification_email_addresses  = 'a@webhook.com, b@webhook.com';

        $form->setValues(
            [
                'config[coreconfig][site_url]'                                       => 'https://mautic-community.local', // required
                'config[leadconfig][contact_columns]'                                => ['name', 'email', 'id'],
                'config[notification_config][campaign_send_notification_to_author]'  => $send_notification_to_author,
                'config[notification_config][campaign_notification_email_addresses]' => $campaign_notification_email_addresses,
                'config[notification_config][webhook_send_notification_to_author]'   => $send_notification_to_author,
                'config[notification_config][webhook_notification_email_addresses]'  => $webhook_notification_email_addresses,
            ]
        );

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();

        Assert::assertEquals($send_notification_to_author, $form['config[notification_config][campaign_send_notification_to_author]']->getValue());
        Assert::assertEquals($campaign_notification_email_addresses, $form['config[notification_config][campaign_notification_email_addresses]']->getValue());
        Assert::assertEquals($send_notification_to_author, $form['config[notification_config][webhook_send_notification_to_author]']->getValue());
        Assert::assertEquals($webhook_notification_email_addresses, $form['config[notification_config][webhook_notification_email_addresses]']->getValue());
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
        Assert::assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

        // 2. Change system locale in configuration - should not change _locale session
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit');
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
        Assert::assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

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
        Assert::assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));

        // 2. Change system locale in configuration to en_US - should change _locale session
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit');
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
        Assert::assertSame('en_US', $this->client->getRequest()->getSession()->get('_locale'));
    }

    public function testSSOSettingEntityId(): void
    {
        $configCrawler    = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $configSaveButton = $configCrawler->selectButton('config[buttons][apply]');
        $configForm       = $configSaveButton->form();

        /** @var ChoiceFormField $entityIdField */
        $entityIdField    = $configForm['config[userconfig][saml_idp_entity_id]'];
        $availableOptions = $entityIdField->availableOptionValues();
        Assert::assertCount(3, $availableOptions);
        $configForm->setValues(
            [
                'config[userconfig][saml_idp_entity_id]'   => $availableOptions[1],
                'config[coreconfig][site_url]'             => 'https://mautic-cloud.local', // required
            ]
        );
        $this->client->submit($configForm);
        $this->assertResponseIsSuccessful();
        Assert::assertEquals($availableOptions[1], $configForm['config[userconfig][saml_idp_entity_id]']->getValue());
    }
}
