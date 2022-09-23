<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Controller;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var string
     */
    private $prefix;

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['config_allowed_parameters'] = [
            'kernel.root_dir',
            'kernel.project_dir',
        ];

        $this->configParams['locale'] = 'en_US';

        parent::setUp();

        $this->prefix = MAUTIC_TABLE_PREFIX;

        $configPath = $this->getConfigPath();
        if (file_exists($configPath)) {
            // backup original local.php
            copy($configPath, $configPath.'.backup');
        } else {
            // write a temporary local.php
            file_put_contents($configPath, '<?php $parameters = [];');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->getConfigPath().'.backup')) {
            // restore original local.php
            rename($this->getConfigPath().'.backup', $this->getConfigPath());
        } else {
            // local.php didn't exist to start with so delete
            unlink($this->getConfigPath());
        }

        parent::tearDown();
    }

    public function testValuesAreEscapedProperly(): void
    {
        $url             = 'https://test.us/create?key=2MLzQFXBSqd2nqwGero90CpB1jX1FbVhhRd51ojr&domain=https%3A%2F%2Ftest.us%2F&longUrl=';
        $trackIps        = "%ip1%\n%ip2%\n%kernel.root_dir%\n%kernel.project_dir%";
        $googleAnalytics = 'reveal pass: %mautic.db_password%';

        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        // Find save & close button
        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        $form->setValues(
            [
                'config[coreconfig][site_url]'           => 'https://mautic-community.local', // required
                'config[coreconfig][link_shortener_url]' => $url,
                'config[coreconfig][do_not_track_ips]'   => $trackIps,
                'config[pageconfig][google_analytics]'   => $googleAnalytics,
                'config[leadconfig][contact_columns]'    => ['name', 'email', 'id'],
            ]
        );

        $crawler = $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

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
        Assert::assertArrayHasKey('link_shortener_url', $configParameters);
        Assert::assertSame($this->escape($url), $configParameters['link_shortener_url']);
        Assert::assertArrayHasKey('do_not_track_ips', $configParameters);
        Assert::assertSame(
            [
                $this->escape('%ip1%'),
                $this->escape('%ip2%'),
                '%kernel.root_dir%',
                '%kernel.project_dir%',
            ],
            $configParameters['do_not_track_ips']
        );
        Assert::assertArrayHasKey('google_analytics', $configParameters);
        Assert::assertSame($this->escape($googleAnalytics), $configParameters['google_analytics']);
        // Check values are unescaped properly in the edit form
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        Assert::assertEquals($url, $form['config[coreconfig][link_shortener_url]']->getValue());
        Assert::assertEquals($trackIps, $form['config[coreconfig][do_not_track_ips]']->getValue());
        Assert::assertEquals($googleAnalytics, $form['config[pageconfig][google_analytics]']->getValue());
    }

    private function getConfigPath(): string
    {
        return self::$container->get('kernel')->getLocalConfigFile();
    }

    private function getConfigParameters(): array
    {
        include $this->getConfigPath();

        /* @var array $parameters */
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
            'date_added'   => (new DateTime())->format('Y-m-d H:i:s'),
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
            'date_added'   => (new DateTime())->format('Y-m-d H:i:s'),
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
            'date_added'   => (new DateTime())->format('Y-m-d H:i:s'),
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
        Assert::assertTrue($this->client->getResponse()->isOk());

        $crawler       = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        Assert::assertEquals($page3, $form['config[coreconfig][404_page]']->getValue());
        // re-create the Symfony client to make config changes applied
        $this->setUpSymfony($this->configParams);

        // Request not found url page3 page content should be rendered
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/editnotfoundurlblablabla');
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
        Assert::assertTrue($this->client->getResponse()->isOk());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

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
        $accountCrawler    = $this->client->request(Request::METHOD_GET, '/s/account');
        $accountSaveButton = $accountCrawler->selectButton('user[buttons][save]');
        $accountForm       = $accountSaveButton->form();
        $accountForm->setValues(
            [
                'user[locale]' => 'en_US',
            ]
        );
        $this->client->submit($accountForm);
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('en_US', self::$container->get('session')->get('_locale'));

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
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('en_US', self::$container->get('session')->get('_locale'));

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
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('en_US', self::$container->get('session')->get('_locale'));

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
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('en_US', self::$container->get('session')->get('_locale'));
    }
}
