<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Functional;

use Mautic\CoreBundle\Helper\Serializer;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;

final class ConfigControllerTest extends MauticMysqlTestCase
{
    private const string API_KEY = 'abc123-us7';

    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $plugin = new Plugin();
        $plugin->setName('Email Marketing');
        $plugin->setBundle('MauticEmailMarketingBundle');
        $this->em->persist($plugin);

        $this->integration = new Integration();
        $this->integration->setName('Mailchimp');
        $this->integration->setIsPublished(false);
        $this->integration->setPlugin($plugin);
        $this->em->persist($this->integration);

        $this->em->flush();
    }

    public function testApiKeyAndPushFeatureAreSaved(): void
    {
        $crawler = $this->requestConfig();
        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_password'));

        $form = $this->publishForm($crawler);
        $form['integration_config[apiKeys][password]']->setValue(self::API_KEY);
        $feature = $form['integration_config[supportedFeatures][0]'];
        $this->assertInstanceOf(ChoiceFormField::class, $feature);
        $this->assertSame('push_lead', $feature->availableOptionValues()[0]);
        $feature->tick();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $saved = $this->savedIntegration();
        $this->assertTrue($saved['isPublished']);
        $this->assertSame(['push_lead'], $saved['supportedFeatures']);
        $this->assertSame(self::API_KEY, $saved['apiKeys']['password']);

        // The key renders blank, and a blank submission keeps it
        $form = $this->publishForm($this->requestConfig());
        $form['integration_config[apiKeys][password]']->setValue('');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertSame(self::API_KEY, $this->savedIntegration()['apiKeys']['password']);
    }

    public function testApiKeyIsRequiredToPublish(): void
    {
        $this->client->submit($this->publishForm($this->requestConfig()));
        $this->assertResponseIsSuccessful();

        $this->assertFalse($this->savedIntegration()['isPublished']);
    }

    public function testAccountConnectedThroughOauthCanBePublishedWithoutApiKeyAndKeepsItsToken(): void
    {
        $this->integration->setApiKeys(self::getContainer()->get(EncryptionService::class)->encrypt([
            'client_id'     => 'client',
            'client_secret' => 'secret',
            'access_token'  => 'token',
            'api_endpoint'  => 'https://us7.api.mailchimp.com',
        ]));
        $this->em->flush();

        $this->client->submit($this->publishForm($this->requestConfig()));
        $this->assertResponseIsSuccessful();

        $saved = $this->savedIntegration();
        $this->assertTrue($saved['isPublished']);
        $keys = $saved['apiKeys'];
        $this->assertSame('token', $keys['access_token']);
        $this->assertSame('https://us7.api.mailchimp.com', $keys['api_endpoint']);
    }

    private function requestConfig(): Crawler
    {
        // Loading the plugin list routes the config page to the IntegrationsBundle controller
        $this->client->request('GET', '/s/plugins');
        $crawler = $this->client->request('GET', self::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => 'Mailchimp']));
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function publishForm(Crawler $crawler): Form
    {
        $form        = $crawler->selectButton('Save & Close')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');

        return $form;
    }

    /**
     * Reads the saved row directly; clearing the entity manager would detach the entities the services hold.
     *
     * @return array{isPublished: bool, supportedFeatures: mixed, apiKeys: array<string, mixed>}
     */
    private function savedIntegration(): array
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT is_published, supported_features, api_keys FROM '.MAUTIC_TABLE_PREFIX.'plugin_integration_settings WHERE name = ?',
            ['Mailchimp']
        );
        $this->assertIsArray($row);

        $apiKeys = Serializer::decode((string) $row['api_keys']);
        $this->assertIsArray($apiKeys);

        return [
            'isPublished'       => (bool) $row['is_published'],
            'supportedFeatures' => Serializer::decode((string) $row['supported_features']),
            'apiKeys'           => self::getContainer()->get(EncryptionService::class)->decrypt($apiKeys),
        ];
    }
}
