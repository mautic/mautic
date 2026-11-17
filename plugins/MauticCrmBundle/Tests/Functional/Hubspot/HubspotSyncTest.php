<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Functional\Hubspot;

use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\Migrations\Version20261112150000;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotApiFake;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HubspotSyncTest extends MauticMysqlTestCase
{
    /**
     * @var array<string, list<array<string, mixed>>> queued HubSpot answers by "METHOD path"
     */
    private array $answers = [];

    /**
     * @var array<string, list<mixed>> JSON bodies sent to HubSpot by "METHOD path"
     */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $httpClient = self::getContainer()->get(HttpClientInterface::class);
        $this->assertInstanceOf(MockHttpClient::class, $httpClient);
        $httpClient->setResponseFactory(function (string $method, string $url, array $options): MockResponse {
            $route                = $method.' '.parse_url($url, PHP_URL_PATH);
            $this->sent[$route][] = isset($options['body']) && '' !== $options['body'] ? json_decode((string) $options['body'], true) : null;

            $body = match ($route) {
                'GET /crm/v3/properties/contacts'  => ['results' => HubspotApiFake::CONTACT_PROPERTIES],
                'GET /crm/v3/properties/companies' => ['results' => HubspotApiFake::COMPANY_PROPERTIES],
                default                            => array_shift($this->answers[$route]) ?? self::fail('Unexpected HubSpot request: '.$route),
            };

            return new MockResponse((string) json_encode($body));
        });
    }

    public function testConfigFormSavesTheTokenEncryptedAndKeepsItWhenSubmittedBlank(): void
    {
        $this->saveSettings(false, []);

        $this->submitConfigForm('pat-first');
        $this->assertSame('pat-first', $this->getSavedToken());

        // the password field renders empty; submitting it blank keeps the token
        $this->submitConfigForm('');
        $this->assertSame('pat-first', $this->getSavedToken());
    }

    public function testWebhookPullsTheNotifiedContactWithItsStageAndCompany(): void
    {
        $this->saveSettings(true, [HubspotClient::CONTACTS]);

        $company = new Company();
        $company->setName('Acme');
        $this->em->persist($company);
        $this->em->flush();
        self::getContainer()->get(ObjectMappingRepository::class)->insert(HubspotIntegration::NAME, HubspotClient::COMPANIES, '900', 'company', $company->getId());

        $this->answers['POST /crm/v3/objects/contacts/batch/read'][] = ['results' => [[
            'id'         => '51',
            'properties' => ['email' => 'john@example.com', 'firstname' => 'John', 'lifecyclestage' => 'customer', 'associatedcompanyid' => '900'],
            'updatedAt'  => '2026-11-12T08:00:00Z',
        ]]];

        $this->client->request('POST', '/plugin/Hubspot/contact_data', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode([
            ['subscriptionType' => 'contact.creation', 'objectId' => 51],
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertSame('OK', $this->client->getResponse()->getContent());

        $this->em->clear();
        $contact = self::getContainer()->get(LeadRepository::class)->findOneBy(['email' => 'john@example.com']);
        $this->assertInstanceOf(Lead::class, $contact);
        $this->assertSame('John', $contact->getFirstname());
        $this->assertSame('customer', $contact->getStage()?->getName());
        $this->assertSame([(string) $company->getId()], array_map(static fn (array $row): string => (string) $row['company_id'], $this->connection->fetchAllAssociative(
            sprintf('SELECT company_id FROM %scompanies_leads WHERE lead_id = :id', MAUTIC_TABLE_PREFIX),
            ['id' => $contact->getId()]
        )));

        $mapping = self::getContainer()->get(ObjectMappingRepository::class)->getInternalObject(HubspotIntegration::NAME, HubspotClient::CONTACTS, '51', Contact::NAME);
        $this->assertSame($contact->getId(), (int) $mapping['internal_object_id']);
    }

    public function testPushActionCreatesTheContactInHubspot(): void
    {
        $this->saveSettings(true, [HubspotClient::CONTACTS]);

        $contact = new Lead();
        $contact->setEmail('jane@example.com');
        $contact->setFirstname('Jane');
        $this->em->persist($contact);
        $this->em->flush();

        $this->answers['POST /crm/v3/objects/contacts/batch/upsert'][] = ['results' => [['id' => '700', 'properties' => ['email' => 'jane@example.com']]]];

        self::getContainer()->get(HubspotIntegration::class)->pushContact($contact, []);

        $this->assertSame(
            [['idProperty' => 'email', 'id' => 'jane@example.com', 'properties' => ['email' => 'jane@example.com', 'firstname' => 'Jane']]],
            $this->sent['POST /crm/v3/objects/contacts/batch/upsert'][0]['inputs']
        );

        $mapping = self::getContainer()->get(ObjectMappingRepository::class)->getIntegrationObject(HubspotIntegration::NAME, Contact::NAME, $contact->getId(), HubspotClient::CONTACTS);
        $this->assertSame('700', $mapping['integration_object_id'] ?? null);
    }

    public function testMigrationMovesTheLegacyMappingAndLinks(): void
    {
        $settings = new Integration();
        $settings->setName(HubspotIntegration::NAME);
        $settings->setIsPublished(true);
        $settings->setSupportedFeatures(['push_lead', 'get_leads']);
        $settings->setFeatureSettings([
            'objects'               => ['contacts', 'company'],
            'leadFields'            => ['email' => 'email', 'firstname' => 'firstname', 'hs_mautic_id' => 'mauticContactId'],
            'update_mautic'         => ['email' => 1, 'firstname' => 0],
            'companyFields'         => ['name' => 'companyname'],
            'update_mautic_company' => ['name' => 1],
        ]);
        $this->em->persist($settings);
        $this->em->flush();

        $insertLink = fn (string $integration, string $entity, string $entityId, string $internal, int $internalId, string $dateAdded): int|string => $this->connection->insert(MAUTIC_TABLE_PREFIX.'integration_entity', [
            'integration'           => $integration,
            'integration_entity'    => $entity,
            'integration_entity_id' => $entityId,
            'internal_entity'       => $internal,
            'internal_entity_id'    => $internalId,
            'date_added'            => $dateAdded,
        ]);
        $insertLink('Hubspot', 'contacts', '51', 'lead', 10, '2026-01-01 00:00:00');
        // pushed again later: the newest link wins
        $insertLink('Hubspot', 'contacts', '52', 'lead', 10, '2026-02-01 00:00:00');
        $insertLink('Salesforce', 'Lead', 'sf-1', 'lead', 11, '2026-01-01 00:00:00');

        $schema = $this->connection->createSchemaManager()->introspectSchema();
        $this->createMigration()->preUp($schema);
        $this->createMigration()->up($schema);

        $this->em->clear();
        $saved = self::getContainer()->get(IntegrationRepository::class)->findOneByName(HubspotIntegration::NAME);
        $this->assertSame(['push_lead', 'sync'], $saved->getSupportedFeatures());
        $this->assertSame([
            'sync' => [
                'objects'       => [HubspotClient::CONTACTS, HubspotClient::COMPANIES],
                'fieldMappings' => [
                    HubspotClient::CONTACTS => [
                        'email'        => ['mappedField' => 'email', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                        'firstname'    => ['mappedField' => 'firstname', 'syncDirection' => ObjectMappingDAO::SYNC_TO_INTEGRATION],
                        'hs_mautic_id' => ['mappedField' => 'mautic_internal_id', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                    ],
                    HubspotClient::COMPANIES => [
                        'name' => ['mappedField' => 'companyname', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                    ],
                ],
            ],
        ], $saved->getFeatureSettings());

        $links = $this->connection->fetchAllAssociative(sprintf('SELECT integration, integration_object_name, integration_object_id, internal_object_name, internal_object_id FROM %ssync_object_mapping', MAUTIC_TABLE_PREFIX));
        $this->assertSame([[
            'integration'             => 'Hubspot',
            'integration_object_name' => 'contacts',
            'integration_object_id'   => '52',
            'internal_object_name'    => 'lead',
            'internal_object_id'      => '10',
        ]], array_map(static fn (array $link): array => array_map(strval(...), $link), $links));

        // nothing left to migrate: the migration is skipped
        $this->expectException(SkipMigration::class);
        $this->createMigration()->preUp($schema);
    }

    private function submitConfigForm(string $token): void
    {
        // the plugin list creates the settings row the config page needs
        $this->client->request('GET', '/s/plugins');
        $crawler = $this->client->request('GET', '/s/plugins/config/Hubspot');
        $this->assertResponseIsSuccessful();

        $form        = $crawler->selectButton('Save & Close')->form();
        $isPublished = $form['integration_config[isPublished]'];
        $this->assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');
        $form['integration_config[apiKeys]['.Config::ACCESS_TOKEN.']']->setValue($token);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }

    private function getSavedToken(): ?string
    {
        $this->em->clear();
        $settings = self::getContainer()->get(IntegrationRepository::class)->findOneByName(HubspotIntegration::NAME);
        $keys     = self::getContainer()->get(EncryptionService::class)->decrypt($settings->getApiKeys());

        return $keys[Config::ACCESS_TOKEN] ?? null;
    }

    /**
     * @param string[] $objects
     */
    private function saveSettings(bool $isPublished, array $objects): void
    {
        $settings = new Integration();
        $settings->setName(HubspotIntegration::NAME);
        $settings->setIsPublished($isPublished);
        $settings->setSupportedFeatures(['sync', 'push_lead']);
        $settings->setApiKeys($isPublished ? [Config::ACCESS_TOKEN => 'pat-token'] : []);
        $settings->setFeatureSettings([
            'sync' => [
                'objects'       => $objects,
                'fieldMappings' => [
                    HubspotClient::CONTACTS => [
                        'email'     => ['mappedField' => 'email', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                        'firstname' => ['mappedField' => 'firstname', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                    ],
                ],
            ],
        ]);

        self::getContainer()->get(IntegrationsHelper::class)->saveIntegrationConfiguration($settings);
        // the saved entity keeps its keys decrypted; load it again as stored
        $this->em->clear();
    }

    private function createMigration(): Version20261112150000
    {
        $migration = new Version20261112150000($this->connection, new NullLogger());
        $migration->setPrefix(MAUTIC_TABLE_PREFIX);

        return $migration;
    }
}
