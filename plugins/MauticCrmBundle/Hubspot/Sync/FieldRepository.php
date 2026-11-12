<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Exception\HubspotApiException;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\DTO\Field;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\DTO\MappedFieldInfo;

/**
 * The properties of the HubSpot objects, cached for an hour.
 */
class FieldRepository
{
    private const int CACHE_TTL = 3600;

    /**
     * Properties that must be mapped, by object.
     */
    private const array REQUIRED = [
        HubspotClient::CONTACTS  => ['email'],
        HubspotClient::COMPANIES => ['name'],
    ];

    /**
     * @var array<string, array<string, Field>>
     */
    private array $fields = [];

    public function __construct(
        private readonly HubspotClient $client,
        private readonly CacheProviderInterface $cacheProvider,
    ) {
    }

    /**
     * @return array<string, Field> by property name
     *
     * @throws HubspotApiException
     */
    public function getFields(string $object): array
    {
        if (isset($this->fields[$object])) {
            return $this->fields[$object];
        }

        $item = $this->cacheProvider->getItem('hubspot.properties.'.$object);
        if ($item->isHit()) {
            /** @var list<array<string, mixed>> $properties */
            $properties = $item->get();
        } else {
            $properties = array_values(array_filter(
                $this->client->getProperties($object),
                static fn (array $property): bool => empty($property['hidden'])
            ));
            $item->set($properties);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cacheProvider->save($item);
        }

        $this->fields[$object] = [];
        foreach ($properties as $property) {
            $field                                       = Field::fromProperty($property, in_array($property['name'], self::REQUIRED[$object] ?? [], true));
            $this->fields[$object][$field->getName()] = $field;
        }

        return $this->fields[$object];
    }

    /**
     * @return array<string, MappedFieldInfo>
     */
    public function getRequiredFieldsForMapping(string $object): array
    {
        return $this->getFieldsForMapping($object, true);
    }

    /**
     * @return array<string, MappedFieldInfo>
     */
    public function getOptionalFieldsForMapping(string $object): array
    {
        return $this->getFieldsForMapping($object, false);
    }

    /**
     * @return array<string, MappedFieldInfo>
     */
    private function getFieldsForMapping(string $object, bool $required): array
    {
        try {
            $available = $this->getFields($object);
        } catch (HubspotApiException) {
            // The settings page still opens while the token is missing or invalid
            return [];
        }

        $fields = [];
        foreach ($available as $name => $field) {
            if ($field->isRequired() === $required) {
                $fields[$name] = new MappedFieldInfo($field);
            }
        }

        uasort($fields, static fn (MappedFieldInfo $a, MappedFieldInfo $b): int => strnatcasecmp($a->getLabel(), $b->getLabel()));

        return $fields;
    }
}
