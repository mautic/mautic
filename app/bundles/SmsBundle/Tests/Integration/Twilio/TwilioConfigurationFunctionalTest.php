<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\Migrations\Version20261109130000;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Tests\SmsTestHelperTrait;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class TwilioConfigurationFunctionalTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    public function testSaveTwilioConfig(): void
    {
        $this->configureTwilioWithArrayTransport();

        $integration = $this->getService(TwilioIntegration::class);

        $integrationRepository = $this->getService(IntegrationRepository::class);

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

    // MenuBuilder keeps built menus in a static for the whole PHP process, so each left panel test needs its own process.
    #[RunInSeparateProcess]
    public function testPublishingAddsSmsToTheLeftPanel(): void
    {
        $this->configureTwilioWithArrayTransport();

        $this->assertStringContainsString('/s/sms', $this->getRefreshedLeftPanel());
    }

    #[RunInSeparateProcess]
    public function testUnpublishingRemovesSmsFromTheLeftPanel(): void
    {
        // published without rendering the menu, which would then be reused for the response below
        $settings = new Integration();
        $settings->setName(TwilioIntegration::NAME);
        $settings->setIsPublished(true);
        $this->em->persist($settings);
        $this->em->flush();

        $crawler     = $this->client->request('GET', '/s/plugins/config/Twilio');
        $this->assertResponseIsSuccessful();
        $form        = $crawler->selectButton('integration_config[buttons][save]')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('0');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertStringNotContainsString('/s/sms', $this->getRefreshedLeftPanel());
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

    public function testInvalidCsrfTokenDoesNotSaveAnUnpublishedConfiguration(): void
    {
        $this->configureTwilioWithArrayTransport();

        $crawler     = $this->client->request('GET', '/s/plugins/config/Twilio');
        $this->assertResponseIsSuccessful();
        $form        = $crawler->selectButton('integration_config[buttons][save]')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('0');
        $form['integration_config[apiKeys][username]']->setValue('new_username');
        $form['integration_config[_token]']->setValue('invalid');

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $integrationConfig = self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => TwilioIntegration::NAME]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        $this->assertTrue($integrationConfig->getIsPublished());
        $this->assertSame(
            ['username' => 'test_username', 'password' => 'test_password'],
            self::getContainer()->get(EncryptionService::class)->decrypt($integrationConfig->getApiKeys())
        );

        // the form is shown again instead of the modal closing
        $this->assertCount(1, $crawler->filter('#integration_config_apiKeys_username'));
    }

    public function testKeysAreRequiredOnlyWhilePublished(): void
    {
        // opens the config page directly: no settings row exists until the page creates it
        $this->assertNull(self::getContainer()->get(IntegrationRepository::class)->findOneBy(['name' => TwilioIntegration::NAME]));

        $crawler     = $this->client->request('GET', '/s/plugins/config/Twilio');
        $this->assertResponseIsSuccessful();
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

        $schema    = $this->connection->createSchemaManager()->introspectSchema();
        $migration = $this->createMigration();
        // flat legacy keys present: the migration must run
        $migration->preUp($schema);
        $migration->up($schema);

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

        // nothing left to move: the migration is skipped
        $this->expectException(SkipMigration::class);
        $this->createMigration()->preUp($schema);
    }

    private function getRefreshedLeftPanel(): string
    {
        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertSame(1, $response['closeModal'] ?? null);
        $this->assertIsString($response['sidebar'] ?? null);
        $this->assertStringContainsString('<div class="sidebar-content">', $response['sidebar']);

        return $response['sidebar'];
    }

    private function createMigration(): Version20261109130000
    {
        $migration = new Version20261109130000($this->connection, new NullLogger());
        $migration->setPrefix(MAUTIC_TABLE_PREFIX);

        return $migration;
    }
}
