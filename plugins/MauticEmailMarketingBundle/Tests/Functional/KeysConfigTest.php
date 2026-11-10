<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Functional;

use Mautic\CoreBundle\Helper\Serializer;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;

/**
 * The Constant Contact and iContact settings pages; Mailchimp has its own test for its OAuth2 fallback.
 */
final class KeysConfigTest extends MauticMysqlTestCase
{
    /**
     * @param array<string, string> $keys
     */
    #[DataProvider('integrationProvider')]
    public function testKeysAndPushFeatureAreSavedAndTheSecretIsKeptWhenBlank(string $integration, array $keys, string $secret): void
    {
        $this->createIntegration($integration);

        $form = $this->publishForm($this->requestConfig($integration));
        foreach ($keys as $key => $value) {
            $form['integration_config[apiKeys]['.$key.']']->setValue($value);
        }
        $feature = $form['integration_config[supportedFeatures][0]'];
        $this->assertInstanceOf(ChoiceFormField::class, $feature);
        $this->assertSame('push_lead', $feature->availableOptionValues()[0]);
        $feature->tick();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $saved = $this->savedIntegration($integration);
        $this->assertTrue($saved['isPublished']);
        $this->assertSame(['push_lead'], $saved['supportedFeatures']);
        $this->assertSame($keys, array_intersect_key($saved['apiKeys'], $keys));

        $form = $this->publishForm($this->requestConfig($integration));
        $form['integration_config[apiKeys]['.$secret.']']->setValue('');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertSame($keys[$secret], $this->savedIntegration($integration)['apiKeys'][$secret]);
    }

    /**
     * @param array<string, string> $keys
     */
    #[DataProvider('integrationProvider')]
    public function testTheSecretIsRequiredToPublish(string $integration, array $keys, string $secret): void
    {
        $this->createIntegration($integration);

        $form = $this->publishForm($this->requestConfig($integration));
        unset($keys[$secret]);
        foreach ($keys as $key => $value) {
            $form['integration_config[apiKeys]['.$key.']']->setValue($value);
        }
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertFalse($this->savedIntegration($integration)['isPublished']);
    }

    /**
     * @return iterable<string, array{string, array<string, string>, string}>
     */
    public static function integrationProvider(): iterable
    {
        yield 'Constant Contact' => ['ConstantContact', ['client_id' => 'api-key', 'access_token' => 'token'], 'access_token'];
        yield 'iContact' => ['Icontact', ['API-AppId' => 'app', 'API-Username' => 'user', 'API-Password' => 'secret'], 'API-Password'];
    }

    private function createIntegration(string $name): void
    {
        $plugin = new Plugin();
        $plugin->setName('Email Marketing');
        $plugin->setBundle('MauticEmailMarketingBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName($name);
        $integration->setIsPublished(false);
        $integration->setPlugin($plugin);
        $this->em->persist($integration);

        $this->em->flush();
    }

    private function requestConfig(string $integration): Crawler
    {
        // Loading the plugin list routes the config page to the IntegrationsBundle controller
        $this->client->request('GET', '/s/plugins');
        $crawler = $this->client->request('GET', self::getContainer()->get(RouterInterface::class)->generate('mautic_plugin_config', ['name' => $integration]));
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
    private function savedIntegration(string $name): array
    {
        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT is_published, supported_features, api_keys FROM '.MAUTIC_TABLE_PREFIX.'plugin_integration_settings WHERE name = ?',
            [$name]
        );
        $this->assertIsArray($row);

        $apiKeys = Serializer::decode((string) $row['api_keys']);

        return [
            'isPublished'       => (bool) $row['is_published'],
            'supportedFeatures' => Serializer::decode((string) $row['supported_features']),
            'apiKeys'           => is_array($apiKeys) ? self::getContainer()->get(EncryptionService::class)->decrypt($apiKeys) : [],
        ];
    }
}
