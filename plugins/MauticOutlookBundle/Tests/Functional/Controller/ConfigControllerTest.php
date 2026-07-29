<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticOutlookBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Routing\RouterInterface;

final class ConfigControllerTest extends MauticMysqlTestCase
{
    private const SECRET = 'test_outlook_secret_123';

    private string $configRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $plugin = new Plugin();
        $plugin->setName('Outlook');
        $plugin->setBundle('MauticOutlookBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName('Outlook');
        $integration->setIsPublished(false);
        $integration->setPlugin($plugin);
        $this->em->persist($integration);

        $this->em->flush();

        $this->configRoute = static::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => 'Outlook']);
    }

    public function testConfigFormRendersSecretFieldAndCopyableMauticUrl(): void
    {
        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();

        // Secret field from the custom auth form.
        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_secret'));

        // The custom content template must still render the copyable, read-only Mautic URL note.
        $readonlyUrl = $crawler->filter('input[readonly]')->reduce(
            fn ($node): bool => str_contains((string) $node->attr('value'), '/index.php')
        );
        $this->assertGreaterThan(0, $readonlyUrl->count(), 'The copyable Mautic URL input is missing from the config form.');
    }

    public function testSavedSecretIsPublishedEncryptedAtRestAndRedisplayed(): void
    {
        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();

        $form             = $crawler->selectButton('Save & Close')->form();
        $isPublishedField = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublishedField);
        $isPublishedField->select('1');
        $form['integration_config[apiKeys][secret]']->setValue(self::SECRET);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->em->clear();

        $integration = $this->em->getRepository(Integration::class)->findOneBy(['name' => 'Outlook']);
        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertTrue($integration->getIsPublished());
        $this->assertNotSame(self::SECRET, $integration->getApiKeys()['secret'] ?? null);

        /** @var IntegrationsHelper $integrationsHelper */
        $integrationsHelper = static::getContainer()->get(IntegrationsHelper::class);
        /** @var ConfigSupport $configSupport */
        $configSupport = static::getContainer()->get(ConfigSupport::class);
        $decrypted     = $integrationsHelper->getIntegrationConfiguration($configSupport);
        $this->assertSame(self::SECRET, $decrypted->getApiKeys()['secret']);

        $crawler = $this->client->request('GET', $this->configRoute);
        $this->assertResponseIsSuccessful();
        $this->assertSame(self::SECRET, $crawler->filter('#integration_config_apiKeys_secret')->attr('value'));
    }
}
