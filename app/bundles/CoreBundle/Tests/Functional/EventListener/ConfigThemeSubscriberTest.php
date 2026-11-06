<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class ConfigThemeSubscriberTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // The Custom Theme tab is disabled by default.
        if ('testEditorHasCustomThemeTabWhenSettingIsEnabled' === $this->name()) {
            $this->configParams['theme_custom_tab_enabled'] = true;
        }

        parent::setUp();
    }

    public function testCustomThemeTabIsDisabledByDefault(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=themeconfig');
        $this->assertResponseIsSuccessful();

        $this->assertCount(0, $crawler->filter('#config_themeconfig_theme_custom_tab_enabled_1[checked]'));
        $this->assertCount(1, $crawler->filter('#config_themeconfig_theme_custom_tab_enabled_0[checked]'));
    }

    public function testCustomThemeTabCanBeEnabled(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=themeconfig');
        $form    = $crawler->selectButton('config[buttons][apply]')->form();
        $form->setValues(['config[themeconfig][theme_custom_tab_enabled]' => '1']);

        // Required fields of other settings, only present while all of them are rendered in one form
        if ($form->has('config[coreconfig][site_url]')) {
            $form->setValues([
                'config[coreconfig][site_url]'           => 'https://mautic-community.local',
                'config[leadconfig][contact_columns]'    => ['name', 'email', 'id'],
                'config[companyconfig][company_columns]' => ['companyname', 'companyemail', 'companywebsite', 'score', 'leadcount', 'id'],
            ]);
        }

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // The value is stored in the local config file.
        $this->assertTrue((bool) ($this->getConfigParameters()['theme_custom_tab_enabled'] ?? false));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideEditors(): iterable
    {
        // Editor form URL and ID of the Theme tab.
        yield 'email'        => ['/s/emails/new', 'email-container'];
        yield 'landing page' => ['/s/pages/new', 'theme-container'];
    }

    #[DataProvider('provideEditors')]
    public function testEditorHasCustomThemeTabWhenSettingIsEnabled(string $editorUrl, string $themeTabId): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('a[href="#custom-theme-container"]'));
        $this->assertCount(1, $crawler->filter('#custom-theme-container'));
        $this->assertCount(1, $crawler->filter('#'.$themeTabId));
    }

    #[DataProvider('provideEditors')]
    public function testEditorHasNoCustomThemeTabByDefault(string $editorUrl, string $themeTabId): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();

        $this->assertCount(0, $crawler->filter('a[href="#custom-theme-container"]'));
        $this->assertCount(0, $crawler->filter('#custom-theme-container'));
        $this->assertCount(1, $crawler->filter('#'.$themeTabId));
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigParameters(): array
    {
        // A new instance reads the local config file again, so the saved values are returned
        return (new Configurator(self::getContainer()->get(PathsHelper::class)))->getParameters();
    }
}
