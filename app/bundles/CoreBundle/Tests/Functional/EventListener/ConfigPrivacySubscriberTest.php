<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ConfigPrivacySubscriberTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        if ('testProfileImageIsLoadedFromGravatarWhenEnabled' === $this->name()) {
            $this->configParams['gravatar_enabled'] = true;
        }

        parent::setUp();
    }

    public function testPrivacyCentreIsListedWithGravatarOffByDefault(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=privacyconfig');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Privacy Centre', $crawler->filter('.list-group-tabs')->text());
        $this->assertCount(1, $crawler->filter('#config_privacyconfig_gravatar_enabled_0[checked]'));
    }

    public function testGravatarCanBeEnabled(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=privacyconfig');
        $form    = $crawler->selectButton('config[buttons][apply]')->form();
        $form->setValues(['config[privacyconfig][gravatar_enabled]' => '1']);

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
        $this->assertTrue((bool) $this->getConfigParameters()['gravatar_enabled']);
    }

    public function testProfileImageIsNotLoadedFromGravatarByDefault(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/account');
        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('gravatar.com', (string) $this->client->getResponse()->getContent());
    }

    public function testProfileImageIsLoadedFromGravatarWhenEnabled(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/account');
        $this->assertResponseIsSuccessful();

        $this->assertStringStartsWith('https://www.gravatar.com/avatar/', (string) $crawler->filter('img.media-object')->attr('src'));
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
