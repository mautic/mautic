<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\FieldRepository;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

trait HubspotTestTrait
{
    /**
     * @param string[]                                                                  $objects
     * @param array<string, array<string, array{mappedField: string, syncDirection: string}>> $fieldMappings by object
     */
    private function config(array $objects = [HubspotClient::CONTACTS, HubspotClient::COMPANIES], array $fieldMappings = [], bool $createRecords = false): Config
    {
        $config = $this->createStub(Config::class);
        $config->method('getAccessToken')->willReturn('pat-token');
        $config->method('isPublished')->willReturn(true);
        $config->method('createsRecords')->willReturn($createRecords);
        $config->method('getEnabledObjects')->willReturn($objects);
        $config->method('getFieldMappings')->willReturnCallback(static fn (string $object): array => $fieldMappings[$object] ?? []);

        return $config;
    }

    private function fieldRepository(HubspotClient $client): FieldRepository
    {
        $adapter = new ArrayAdapter();
        $cache   = $this->createStub(CacheProviderInterface::class);
        $cache->method('getItem')->willReturnCallback($adapter->getItem(...));
        $cache->method('save')->willReturnCallback($adapter->save(...));

        return new FieldRepository($client, $cache);
    }
}
