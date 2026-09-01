<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticClearbitBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Routing\RouterInterface;

final class ConfigControllerTest extends MauticMysqlTestCase
{
    private const API_KEY = 'test_api_key_123';

    private string $configRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $plugin = new Plugin();
        $plugin->setName('Clearbit');
        $plugin->setBundle('MauticClearbitBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName('Clearbit');
        $integration->setIsPublished(false);
        $integration->setPlugin($plugin);
        $this->em->persist($integration);

        $this->em->flush();

        $this->configRoute = self::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => 'Clearbit']);
    }

    public function testSavedIntegrationIsPublishedAndApiKeyIsEncryptedAtRest(): void
    {
        $this->saveConfigForm();
        $this->em->clear();

        $integration = $this->em->getRepository(Integration::class)->findOneBy(['name' => 'Clearbit']);
        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertTrue($integration->getIsPublished());
        $this->assertNotSame(self::API_KEY, $integration->getApiKeys()['apikey'] ?? null);
    }

    public function testSavedApiKeyAndAutoUpdateDecryptBackToOriginalValues(): void
    {
        $this->saveConfigForm();
        $this->em->clear();

        /** @var IntegrationsHelper $integrationsHelper */
        $integrationsHelper = self::getContainer()->get(IntegrationsHelper::class);
        /** @var ConfigSupport $configSupport */
        $configSupport = self::getContainer()->get(ConfigSupport::class);
        $decrypted     = $integrationsHelper->getIntegrationConfiguration($configSupport);

        $this->assertSame(self::API_KEY, $decrypted->getApiKeys()['apikey']);
        $this->assertTrue((bool) $decrypted->getApiKeys()['auto_update']);
    }

    public function testConfigFormRedisplaysSavedValuesOnReload(): void
    {
        $this->saveConfigForm();

        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();
        $this->assertSame(self::API_KEY, $crawler->filter('#integration_config_apiKeys_apikey')->attr('value'));
        $this->assertNotNull($crawler->filter('#integration_config_isPublished_1')->attr('checked'));
        $this->assertNotNull($crawler->filter('#integration_config_apiKeys_auto_update_1')->attr('checked'));
    }

    private function saveConfigForm(): void
    {
        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save & Close')->form();

        $isPublishedField = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublishedField);
        $isPublishedField->select('1');

        $form['integration_config[apiKeys][apikey]']->setValue(self::API_KEY);

        $autoUpdateField = $form['integration_config[apiKeys][auto_update]'];
        $this->assertInstanceOf(ChoiceFormField::class, $autoUpdateField);
        $autoUpdateField->select('1');

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }
}
