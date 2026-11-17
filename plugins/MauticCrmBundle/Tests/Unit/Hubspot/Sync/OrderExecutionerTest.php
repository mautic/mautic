<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\OrderExecutioner;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ValueNormalizer;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotApiFake;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotTestTrait;
use PHPUnit\Framework\TestCase;

final class OrderExecutionerTest extends TestCase
{
    use HubspotTestTrait;

    private HubspotApiFake $api;

    protected function setUp(): void
    {
        $this->api = new HubspotApiFake();
    }

    public function testUpdatesLinkedContactsWithTheirWritableChangedProperties(): void
    {
        $this->api->queue('POST /crm/v3/objects/contacts/batch/update', [
            'results' => [['id' => '51']],
            'errors'  => [
                ['category' => 'OBJECT_NOT_FOUND', 'message' => 'Not found', 'context' => ['ids' => ['52']]],
                ['category' => 'VALIDATION_ERROR', 'message' => 'Property values were not valid', 'context' => ['ids' => ['53']]],
            ],
        ], 207);

        $order = $this->order();
        $order->addObjectChange($this->contact(1, '51', ['firstname' => 'John', 'interests' => 'golf|tennis', 'hs_analytics_source' => 'DIRECT']));
        $order->addObjectChange($this->contact(2, '52', ['firstname' => 'Jane']));
        $order->addObjectChange($this->contact(3, '53', ['firstname' => 'Joe']));
        $this->executioner()->execute($order);

        $this->assertSame(['inputs' => [
            // the read only "hs_analytics_source" is left out
            ['id' => '51', 'properties' => ['firstname' => 'John', 'interests' => 'golf;tennis']],
            ['id' => '52', 'properties' => ['firstname' => 'Jane']],
            ['id' => '53', 'properties' => ['firstname' => 'Joe']],
        ]], $this->api->sentTo('POST /crm/v3/objects/contacts/batch/update')[0]);

        $this->assertSame(['51'], array_map(static fn (UpdatedObjectMappingDAO $mapping) => $mapping->getIntegrationObjectId(), $order->getUpdatedObjectMappings()));
        $this->assertSame(['52'], array_map(static fn (ObjectChangeDAO $change) => $change->getObjectId(), $order->getDeletedObjects()));
        $this->assertCount(1, $order->getNotifications());
        $this->assertSame('Property values were not valid', $order->getNotifications()[0]->getMessage());
    }

    public function testLeavesUnlinkedContactsAloneByDefault(): void
    {
        $order = $this->order();
        $order->addObjectChange($this->contact(1, null, ['email' => 'john@example.com']));
        $this->executioner()->execute($order);

        $this->assertSame([], $order->getObjectMappings());
        $this->assertSame([], $this->api->sentTo('POST /crm/v3/objects/contacts/batch/upsert'));
    }

    public function testPushActionCreatesUnlinkedContactsMatchedByEmail(): void
    {
        $this->api->queue('POST /crm/v3/objects/contacts/batch/upsert', [
            'results' => [['id' => '700', 'properties' => ['email' => 'john@example.com']]],
        ]);

        $order = $this->order([HubspotIntegration::OPTION_CREATE_RECORDS => true]);
        $order->addObjectChange($this->contact(1, null, ['firstname' => 'John'], ['email' => 'John@Example.com']));
        $order->addObjectChange($this->contact(2, null, ['firstname' => 'No email']));
        $this->executioner()->execute($order);

        $this->assertSame(['inputs' => [
            ['idProperty' => 'email', 'id' => 'john@example.com', 'properties' => ['email' => 'John@Example.com', 'firstname' => 'John']],
        ]], $this->api->sentTo('POST /crm/v3/objects/contacts/batch/upsert')[0]);

        $this->assertCount(1, $order->getObjectMappings());
        $this->assertSame('700', $order->getObjectMappings()[0]->getIntegrationObjectId());
        $this->assertSame(1, $order->getObjectMappings()[0]->getInternalObjectId());
        $this->assertSame('The contact has no email, which HubSpot requires.', $order->getNotifications()[0]->getMessage());
    }

    public function testCreatesCompaniesWhenTheSettingIsOn(): void
    {
        $this->api->queue('POST /crm/v3/objects/companies', ['id' => '900', 'properties' => ['name' => 'Acme']], 201);

        $order = $this->order();
        $order->addObjectChange($this->company(5, ['domain' => 'acme.example'], ['name' => 'Acme']));
        $this->executioner(true)->execute($order);

        $this->assertSame([['properties' => ['name' => 'Acme', 'domain' => 'acme.example']]], $this->api->sentTo('POST /crm/v3/objects/companies'));
        $this->assertSame('900', $order->getObjectMappings()[0]->getIntegrationObjectId());
    }

    public function testRetriesLaterWhenHubspotIsRateLimiting(): void
    {
        $this->api->queue('POST /crm/v3/objects/contacts/batch/update', ['message' => 'You have reached your secondly limit.'], 429);

        $order = $this->order();
        $order->addObjectChange($this->contact(1, '51', ['firstname' => 'John']));
        $this->executioner()->execute($order);

        $this->assertSame([], $order->getSuccessfullySyncedObjects());
        $this->assertSame([], $order->getNotifications());
    }

    private function executioner(bool $createRecords = false): OrderExecutioner
    {
        $config = $this->config(createRecords: $createRecords);
        $client = $this->api->client($config);

        return new OrderExecutioner($client, $config, $this->fieldRepository($client), new ValueNormalizer());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function order(array $options = []): OrderDAO
    {
        return new OrderDAO(new \DateTimeImmutable(), false, HubspotIntegration::NAME, $options);
    }

    /**
     * @param array<string, string> $changed
     * @param array<string, string> $required
     */
    private function contact(int $mauticId, ?string $hubspotId, array $changed, array $required = []): ObjectChangeDAO
    {
        return $this->change(HubspotClient::CONTACTS, Contact::NAME, $mauticId, $hubspotId, $changed, $required);
    }

    /**
     * @param array<string, string> $changed
     * @param array<string, string> $required
     */
    private function company(int $mauticId, array $changed, array $required): ObjectChangeDAO
    {
        return $this->change(HubspotClient::COMPANIES, Company::NAME, $mauticId, null, $changed, $required);
    }

    /**
     * @param array<string, string> $changed
     * @param array<string, string> $required
     */
    private function change(string $object, string $mauticObject, int $mauticId, ?string $hubspotId, array $changed, array $required): ObjectChangeDAO
    {
        $change = new ObjectChangeDAO(HubspotIntegration::NAME, $object, $hubspotId, $mauticObject, $mauticId);
        foreach ($required as $name => $value) {
            $change->addField(new FieldDAO($name, new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, $value)), ReportFieldDAO::FIELD_REQUIRED);
        }
        foreach ($changed as $name => $value) {
            $type = 'interests' === $name ? NormalizedValueDAO::MULTISELECT_TYPE : NormalizedValueDAO::STRING_TYPE;
            $change->addField(new FieldDAO($name, new NormalizedValueDAO($type, $value)));
        }

        return $change;
    }
}
