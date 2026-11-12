<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\MappingManualFactory;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotApiFake;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotTestTrait;
use PHPUnit\Framework\TestCase;

final class MappingManualFactoryTest extends TestCase
{
    use HubspotTestTrait;

    public function testMapsTheEnabledObjectsWithTheSavedDirections(): void
    {
        $config = $this->config([HubspotClient::CONTACTS], [
            HubspotClient::CONTACTS => [
                'email'               => ['mappedField' => 'email', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                'firstname'           => ['mappedField' => 'firstname', 'syncDirection' => ObjectMappingDAO::SYNC_TO_INTEGRATION],
                'hs_analytics_source' => ['mappedField' => 'source', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
                'deleted_property'    => ['mappedField' => 'city', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
            ],
            HubspotClient::COMPANIES => [
                'name' => ['mappedField' => 'companyname', 'syncDirection' => ObjectMappingDAO::SYNC_BIDIRECTIONALLY],
            ],
        ]);
        $client = new HubspotApiFake()->client($config);
        $manual = new MappingManualFactory($config, $this->fieldRepository($client))->getManual();

        // Companies are not enabled
        $this->assertSame([HubspotClient::CONTACTS], $manual->getIntegrationObjectNames());
        $this->assertSame([Contact::NAME], $manual->getMappedInternalObjectsNames(HubspotClient::CONTACTS));
        $this->assertNotContains(Company::NAME, $manual->getInternalObjectNames());

        $mapping = $manual->getObjectMapping(Contact::NAME, HubspotClient::CONTACTS);
        $this->assertInstanceOf(ObjectMappingDAO::class, $mapping);

        $directions = [];
        foreach ($mapping->getFieldMappings() as $fieldMapping) {
            $directions[$fieldMapping->getIntegrationField()] = [$fieldMapping->getInternalField(), $fieldMapping->getSyncDirection(), $fieldMapping->isRequired()];
        }

        $this->assertSame([
            'email'     => ['email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true],
            'firstname' => ['firstname', ObjectMappingDAO::SYNC_TO_INTEGRATION, false],
            // HubSpot does not let us write it
            'hs_analytics_source' => ['source', ObjectMappingDAO::SYNC_TO_MAUTIC, false],
        ], $directions);
    }
}
