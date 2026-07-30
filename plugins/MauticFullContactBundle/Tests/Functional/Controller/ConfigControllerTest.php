<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Routing\RouterInterface;

final class ConfigControllerTest extends MauticMysqlTestCase
{
    private const API_KEY = 'test_fullcontact_key_123';

    private string $configRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $plugin = new Plugin();
        $plugin->setName('FullContact');
        $plugin->setBundle('MauticFullContactBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName('FullContact');
        $integration->setIsPublished(false);
        $integration->setPlugin($plugin);
        $this->em->persist($integration);

        $this->em->flush();

        $this->configRoute = static::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => 'FullContact']);
    }

    public function testConfigFormRendersKeysFieldsAndCopyableWebhookUrl(): void
    {
        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_apikey'));
        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_stats'));
        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_test_api'));

        $readonlyUrl = $crawler->filter('input[readonly]')->reduce(
            fn ($node): bool => str_contains((string) $node->attr('value'), '/fullcontact/callback')
        );
        $this->assertGreaterThan(0, $readonlyUrl->count(), 'The copyable FullContact webhook URL input is missing from the config form.');
    }

    public function testSavedApiKeyIsPublishedEncryptedAtRestAndRedisplayed(): void
    {
        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();

        $form             = $crawler->selectButton('Save & Close')->form();
        $isPublishedField = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublishedField);
        $isPublishedField->select('1');
        $form['integration_config[apiKeys][apikey]']->setValue(self::API_KEY);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->em->clear();

        $integration = $this->em->getRepository(Integration::class)->findOneBy(['name' => 'FullContact']);
        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertTrue($integration->getIsPublished());
        $this->assertNotSame(self::API_KEY, $integration->getApiKeys()['apikey'] ?? null);

        /** @var IntegrationsHelper $integrationsHelper */
        $integrationsHelper = static::getContainer()->get(IntegrationsHelper::class);
        /** @var ConfigSupport $configSupport */
        $configSupport = static::getContainer()->get(ConfigSupport::class);
        $decrypted     = $integrationsHelper->getIntegrationConfiguration($configSupport);
        $this->assertSame(self::API_KEY, $decrypted->getApiKeys()['apikey']);

        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();
        $this->assertSame(self::API_KEY, $crawler->filter('#integration_config_apiKeys_apikey')->attr('value'));
    }
}
