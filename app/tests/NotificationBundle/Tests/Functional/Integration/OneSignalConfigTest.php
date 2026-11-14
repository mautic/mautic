<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Functional\Integration;

use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\Migrations\Version20261109120000;
use Mautic\NotificationBundle\Helper\OneSignalIntegrationHelper;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class OneSignalConfigTest extends MauticMysqlTestCase
{
    public function testHelperReturnsNullWithoutSettingsRow(): void
    {
        $this->assertNull(self::getContainer()->get(OneSignalIntegrationHelper::class)->getIntegration());
    }

    public function testConfigFormSavesKeysEncryptedAndFeatureSettingsUnderIntegrationKey(): void
    {
        $this->saveSettings(new Integration(), false, []);

        $crawler = $this->client->request('GET', '/s/plugins/config/OneSignal');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save & Close')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');
        $form['integration_config[apiKeys][app_id]']->setValue('app-id');
        $form['integration_config[apiKeys][safari_web_id]']->setValue('safari-id');
        $form['integration_config[apiKeys][rest_api_key]']->setValue('rest-key');
        $form['integration_config[apiKeys][gcm_sender_id]']->setValue('gcm-id');
        $form['integration_config[featureSettings][integration][subdomain_name]']->setValue('acme');

        $values                                                       = $form->getPhpValues();
        $values['integration_config']['supportedFeatures']            = ['mobile', 'landing_page_enabled'];
        $values['integration_config']['featureSettings']['integration']['platforms'] = ['ios'];
        $this->client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $settings = self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => OneSignalIntegration::NAME]);
        $this->assertInstanceOf(Integration::class, $settings);
        $this->assertTrue($settings->getIsPublished());
        $this->assertSame(['mobile', 'landing_page_enabled'], $settings->getSupportedFeatures());
        $this->assertSame(['subdomain_name' => 'acme', 'platforms' => ['ios']], $settings->getFeatureSettings()['integration']);
        $this->assertNotSame('rest-key', $settings->getApiKeys()['rest_api_key']);
        $this->assertSame(
            ['app_id' => 'app-id', 'safari_web_id' => 'safari-id', 'rest_api_key' => 'rest-key', 'gcm_sender_id' => 'gcm-id'],
            self::getContainer()->get(EncryptionService::class)->decrypt($settings->getApiKeys())
        );
    }

    public function testHelperReturnsPublishedIntegrationWithDecryptedKeys(): void
    {
        $settings = new Integration();
        $settings->setApiKeys(self::getContainer()->get(EncryptionService::class)->encrypt(['rest_api_key' => 'rest-key']));
        $this->saveSettings($settings, true, ['integration' => ['platforms' => ['android']]]);

        $integration = self::getContainer()->get(OneSignalIntegrationHelper::class)->getPublishedIntegration();

        $this->assertInstanceOf(OneSignalIntegration::class, $integration);
        $this->assertSame(['rest_api_key' => 'rest-key'], $integration->getIntegrationConfiguration()->getApiKeys());
        $this->assertSame(['platforms' => ['android']], $integration->getIntegrationFeatureSettings());
    }

    public function testHelperIgnoresUnpublishedIntegration(): void
    {
        $this->saveSettings(new Integration(), false, []);

        $helper = self::getContainer()->get(OneSignalIntegrationHelper::class);

        $this->assertInstanceOf(OneSignalIntegration::class, $helper->getIntegration());
        $this->assertNull($helper->getPublishedIntegration());
    }

    public function testKeysAreRequiredOnlyWhilePublished(): void
    {
        $this->saveSettings(new Integration(), false, []);

        $crawler = $this->client->request('GET', '/s/plugins/config/OneSignal');
        $form    = $crawler->selectButton('Save & Close')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $crawler->filter('.has-error #integration_config_apiKeys_app_id, .has-error #integration_config_apiKeys_safari_web_id, .has-error #integration_config_apiKeys_rest_api_key, .has-error #integration_config_apiKeys_gcm_sender_id'));

        $this->em->clear();
        $settings = self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => OneSignalIntegration::NAME]);
        $this->assertInstanceOf(Integration::class, $settings);
        $this->assertFalse($settings->getIsPublished());
    }

    public function testMigrationMovesLegacyFlatFeatureSettings(): void
    {
        $settings = $this->saveSettings(new Integration(), true, [
            'subdomain_name' => 'acme',
            'platforms'      => ['ios', 'android'],
        ]);

        $schema    = $this->connection->createSchemaManager()->introspectSchema();
        $migration = $this->createMigration();
        // flat legacy keys present: the migration must run
        $migration->preUp($schema);
        $migration->up($schema);

        $this->em->clear();
        $settings = self::getContainer()->get(IntegrationRepository::class)->find($settings->getId());
        $this->assertInstanceOf(Integration::class, $settings);
        $this->assertSame(
            ['integration' => ['subdomain_name' => 'acme', 'platforms' => ['ios', 'android']]],
            $settings->getFeatureSettings()
        );

        // nothing left to move: the migration is skipped
        $this->expectException(SkipMigration::class);
        $this->createMigration()->preUp($schema);
    }

    private function createMigration(): Version20261109120000
    {
        $migration = new Version20261109120000($this->connection, new NullLogger());
        $migration->setPrefix(MAUTIC_TABLE_PREFIX);

        return $migration;
    }

    /**
     * @param array<string, mixed> $featureSettings
     */
    private function saveSettings(Integration $settings, bool $isPublished, array $featureSettings): Integration
    {
        $settings->setName(OneSignalIntegration::NAME);
        $settings->setIsPublished($isPublished);
        $settings->setFeatureSettings($featureSettings);
        $this->em->persist($settings);
        $this->em->flush();

        return $settings;
    }
}
