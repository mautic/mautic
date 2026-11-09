<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\Migrations\Version20261109130000;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Tests\SmsTestHelperTrait;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class TwilioConfigurationFunctionalTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    public function testSaveTwilioConfig(): void
    {
        $this->configureTwilioWithArrayTransport();

        /** @var TwilioIntegration $integration */
        $integration = $this->getContainer()->get(TwilioIntegration::class);
        $this->assertInstanceOf(TwilioIntegration::class, $integration);

        $integrationRepository = $this->getContainer()->get(IntegrationRepository::class);

        // the saved entity keeps decrypted keys in memory; read the stored row
        $this->em->clear();
        $integrationConfig = $integrationRepository->findOneBy(['name' => $integration->getName()]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        $this->assertSame('messaging_sid', $integrationConfig->getFeatureSettings()['integration']['messaging_service_sid']);
        $this->assertSame(
            ['username' => 'test_username', 'password' => 'test_password'],
            self::getContainer()->get(EncryptionService::class)->decrypt($integrationConfig->getApiKeys())
        );
    }

    public function testBlankAuthTokenKeepsTheSavedOne(): void
    {
        $this->configureTwilioWithArrayTransport();

        $crawler = $this->client->request('GET', '/s/plugins/config/Twilio');
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $crawler->filter('#integration_config_apiKeys_password')->attr('value') ?? '');

        $form = $crawler->selectButton('integration_config[buttons][save]')->form();
        $form['integration_config[apiKeys][username]']->setValue('new_username');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $integrationConfig = self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => TwilioIntegration::NAME]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        $this->assertSame(
            ['username' => 'new_username', 'password' => 'test_password'],
            self::getContainer()->get(EncryptionService::class)->decrypt($integrationConfig->getApiKeys())
        );
    }

    public function testKeysAreRequiredOnlyWhilePublished(): void
    {
        $this->testSymfonyCommand('mautic:plugins:install');
        $this->client->request('GET', '/s/plugins');

        $crawler     = $this->client->request('GET', '/s/plugins/config/Twilio');
        $form        = $crawler->selectButton('integration_config[buttons][save]')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.has-error #integration_config_apiKeys_username, .has-error #integration_config_apiKeys_password'));

        $this->em->clear();
        $integrationConfig = self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => TwilioIntegration::NAME]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        $this->assertFalse($integrationConfig->getIsPublished());
    }

    public function testMigrationMovesLegacyFlatFeatureSettings(): void
    {
        $settings = new Integration();
        $settings->setName(TwilioIntegration::NAME);
        $settings->setFeatureSettings([
            'messaging_service_sid' => 'MG123',
            'frequency_number'      => 5,
            'frequency_time'        => 'WEEK',
            'disable_trackable_urls' => true,
        ]);
        $this->em->persist($settings);
        $this->em->flush();

        $migration = new Version20261109130000($this->connection, new NullLogger());
        $migration->setPrefix(MAUTIC_TABLE_PREFIX);
        $migration->up($this->connection->createSchemaManager()->introspectSchema());

        $this->em->clear();
        $settings = self::getContainer()->get(IntegrationRepository::class)->find($settings->getId());
        $this->assertInstanceOf(Integration::class, $settings);
        $this->assertSame(
            [
                'disable_trackable_urls' => true,
                'integration'            => ['messaging_service_sid' => 'MG123', 'frequency_number' => 5, 'frequency_time' => 'WEEK'],
            ],
            $settings->getFeatureSettings()
        );
    }
}
