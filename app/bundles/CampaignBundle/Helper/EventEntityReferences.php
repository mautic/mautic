<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Helper;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\StageBundle\Entity\Stage;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

/**
 * Campaign event properties that refer to other entities, so the export can take those entities along
 * and the import can point the properties to the right entities on the target instance.
 */
final class EventEntityReferences
{
    public const COMPANY = 'company';

    public const FOCUS = 'focus';

    public const MESSAGE = 'message';

    public const NOTIFICATION = 'notification';

    public const SMS = 'sms';

    public const STAGE = 'stage';

    public const TAG = 'tag';

    public const USER = 'user';

    /**
     * Entities that are exported with the campaign and imported under new IDs.
     */
    private const EXPORTED = [
        Asset::ENTITY_NAME,
        DynamicContent::ENTITY_NAME,
        Email::ENTITY_NAME,
        Form::ENTITY_NAME,
        Group::ENTITY_NAME,
        LeadList::ENTITY_NAME,
        Page::ENTITY_NAME,
    ];

    /**
     * Entities that are not exported, but can be found on the target instance by their UUID.
     */
    public const LINKED = [
        Campaign::ENTITY_NAME => Campaign::class,
        self::FOCUS           => Focus::class,
        self::MESSAGE         => Message::class,
        self::NOTIFICATION    => Notification::class,
        self::SMS             => Sms::class,
        self::STAGE           => Stage::class,
    ];

    /**
     * Property paths per event type, with what they refer to. Tags are referred to by name.
     * Companies and users are neither exported nor identifiable on the target instance, so the import removes them.
     */
    private const REFERENCES = [
        'asset.download'                        => ['assets' => Asset::ENTITY_NAME],
        'campaign.addremovelead'                => ['addTo' => Campaign::ENTITY_NAME, 'removeFrom' => Campaign::ENTITY_NAME],
        'dwc.decision'                          => ['dynamicContent' => DynamicContent::ENTITY_NAME],
        'dwc.push_content'                      => ['dynamicContent' => DynamicContent::ENTITY_NAME],
        'email.send'                            => ['email' => Email::ENTITY_NAME],
        'email.send.to.user'                    => ['useremail.email' => Email::ENTITY_NAME, 'user_id' => self::USER],
        'focus.show'                            => ['focus' => self::FOCUS],
        'form.field_value'                      => ['form' => Form::ENTITY_NAME],
        'form.submit'                           => ['forms' => Form::ENTITY_NAME],
        'lead.addtocompany'                     => ['company' => self::COMPANY],
        'lead.campaigns'                        => ['campaigns' => Campaign::ENTITY_NAME],
        'lead.changelist'                       => ['addToLists' => LeadList::ENTITY_NAME, 'removeFromLists' => LeadList::ENTITY_NAME],
        'lead.changeowner'                      => ['owner' => self::USER],
        'lead.changepoints'                     => ['group' => Group::ENTITY_NAME],
        'lead.changetags'                       => ['add_tags' => self::TAG, 'remove_tags' => self::TAG],
        'lead.owner'                            => ['owner' => self::USER],
        'lead.pageHit'                          => ['page' => Page::ENTITY_NAME],
        'lead.points'                           => ['group' => Group::ENTITY_NAME],
        'lead.segments'                         => ['segments' => LeadList::ENTITY_NAME],
        'lead.stages'                           => ['stages' => self::STAGE],
        'lead.tags'                             => ['tags' => self::TAG],
        'message.send'                          => ['marketingMessage' => self::MESSAGE],
        'notification.send_mobile_notification' => ['notification' => self::NOTIFICATION],
        'notification.send_notification'        => ['notification' => self::NOTIFICATION],
        'page.devicehit'                        => ['pages' => Page::ENTITY_NAME],
        'page.pagehit'                          => ['pages' => Page::ENTITY_NAME],
        'sms.send_text_sms'                     => ['sms' => self::SMS],
        'stage.change'                          => ['stage' => self::STAGE],
    ];

    /**
     * Event types with a property that holds a contact or company field alias.
     */
    private const FIELD_VALUE_REFERENCES = [
        'lead.field_value' => 'field',
    ];

    /**
     * Event types whose properties are keyed by contact or company field aliases.
     */
    private const FIELD_KEY_REFERENCES = [
        'lead.updatecompany',
        'lead.updatelead',
    ];

    /**
     * The event channel names that differ from what they refer to.
     */
    private const CHANNEL_REFERENCES = [
        'channel.message'     => self::MESSAGE,
        'dynamicContent'      => DynamicContent::ENTITY_NAME,
        'form'                => Form::ENTITY_NAME,
        'mobile_notification' => self::NOTIFICATION,
    ];

    public function hasReferences(string $eventType): bool
    {
        return isset(self::REFERENCES[$eventType]) || $this->hasFieldReferences($eventType);
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, list<int>> the IDs of the entities to export per entity name
     */
    public function getExportedIds(string $eventType, array $properties): array
    {
        return $this->getIds($eventType, $properties, self::EXPORTED);
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, list<int>> the IDs of the entities to find by UUID on import, per key of self::LINKED
     */
    public function getLinkedIds(string $eventType, array $properties): array
    {
        return $this->getIds($eventType, $properties, array_keys(self::LINKED));
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return list<string>
     */
    public function getTagNames(string $eventType, array $properties): array
    {
        $names = [];

        foreach (self::REFERENCES[$eventType] ?? [] as $path => $kind) {
            if (self::TAG !== $kind) {
                continue;
            }

            foreach ((array) $this->getValue($properties, $path) as $name) {
                if (is_string($name) && '' !== $name && !is_numeric($name)) {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return list<string> the field aliases the event uses, custom or not
     */
    public function getFieldAliases(string $eventType, array $properties): array
    {
        $values = $this->getFormValues($properties);

        if (isset(self::FIELD_VALUE_REFERENCES[$eventType])) {
            $alias = $values[self::FIELD_VALUE_REFERENCES[$eventType]] ?? null;

            return is_string($alias) && '' !== $alias ? [$alias] : [];
        }

        if (!in_array($eventType, self::FIELD_KEY_REFERENCES, true)) {
            return [];
        }

        // Only the fields with a value are updated
        $aliases = [];
        foreach ($values as $alias => $value) {
            if (is_string($alias) && null !== $value && '' !== $value && [] !== $value) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }

    /**
     * Points the references of an exported event to the entities on the target instance.
     * A reference to an entity that is not there is removed, as the old ID could belong to an unrelated entity.
     *
     * @param array<string, mixed>                  $event
     * @param array<string, array<int|string, int>> $idMaps       the new IDs by the old IDs per entity name or key of self::LINKED
     * @param array<string, string>                 $fieldAliases the new field aliases by the old ones, when they changed
     *
     * @return array<string, mixed>
     */
    public function remapEvent(array $event, array $idMaps, array $fieldAliases = []): array
    {
        $eventType  = (string) ($event['type'] ?? '');
        $references = self::REFERENCES[$eventType] ?? [];

        if (!empty($event['channel']) && !empty($event['channel_id'])) {
            $channelReference = self::CHANNEL_REFERENCES[$event['channel']] ?? $event['channel'];

            if (in_array($channelReference, $references, true)) {
                $event['channel_id'] = $idMaps[$channelReference][(int) $event['channel_id']] ?? null;
            }
        }

        if (!isset($event['properties']) || !is_array($event['properties'])) {
            return $event;
        }

        // The builder stores the form values both at the top level and under "properties"
        $event['properties'] = $this->remapProperties($event['properties'], $eventType, $idMaps, $fieldAliases);

        if (isset($event['properties']['properties']) && is_array($event['properties']['properties'])) {
            $event['properties']['properties'] = $this->remapProperties($event['properties']['properties'], $eventType, $idMaps, $fieldAliases);
        }

        return $event;
    }

    private function hasFieldReferences(string $eventType): bool
    {
        return isset(self::FIELD_VALUE_REFERENCES[$eventType]) || in_array($eventType, self::FIELD_KEY_REFERENCES, true);
    }

    /**
     * @param array<string, mixed> $properties
     * @param list<string>         $kinds
     *
     * @return array<string, list<int>>
     */
    private function getIds(string $eventType, array $properties, array $kinds): array
    {
        $ids = [];

        foreach (self::REFERENCES[$eventType] ?? [] as $path => $kind) {
            if (!in_array($kind, $kinds, true)) {
                continue;
            }

            foreach ($this->toIds($this->getValue($properties, $path)) as $id) {
                $ids[$kind][] = $id;
            }
        }

        return array_map(static fn (array $entityIds): array => array_values(array_unique($entityIds)), $ids);
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    private function getFormValues(array $properties): array
    {
        // The top level also holds the builder's own keys, like name and type
        return isset($properties['properties']) && is_array($properties['properties']) ? $properties['properties'] : $properties;
    }

    /**
     * @param array<string, mixed>                  $properties
     * @param array<string, array<int|string, int>> $idMaps
     * @param array<string, string>                 $fieldAliases
     *
     * @return array<string, mixed>
     */
    private function remapProperties(array $properties, string $eventType, array $idMaps, array $fieldAliases): array
    {
        foreach (self::REFERENCES[$eventType] ?? [] as $path => $kind) {
            $properties = $this->remapValue($properties, $path, $idMaps[$kind] ?? []);
        }

        if ([] === $fieldAliases) {
            return $properties;
        }

        if (isset(self::FIELD_VALUE_REFERENCES[$eventType])) {
            $key = self::FIELD_VALUE_REFERENCES[$eventType];
            if (is_string($properties[$key] ?? null) && isset($fieldAliases[$properties[$key]])) {
                $properties[$key] = $fieldAliases[$properties[$key]];
            }
        }

        if (in_array($eventType, self::FIELD_KEY_REFERENCES, true)) {
            $renamed = [];
            foreach ($properties as $key => $value) {
                $renamed[$fieldAliases[$key] ?? $key] = $value;
            }
            $properties = $renamed;
        }

        return $properties;
    }

    /**
     * Replaces the IDs in the value by the new ones and removes the IDs without a new one. Other values, like tag names, stay.
     *
     * @param array<string, mixed>   $properties
     * @param array<int|string, int> $idMap
     *
     * @return array<string, mixed>
     */
    private function remapValue(array $properties, string $path, array $idMap): array
    {
        $value = $this->getValue($properties, $path);

        if (null === $value || '' === $value || [] === $value) {
            return $properties;
        }

        if (is_array($value)) {
            $newValue = [];
            foreach ($value as $item) {
                if (!$this->isId($item)) {
                    $newValue[] = $item;
                } elseif (isset($idMap[(int) $item])) {
                    $newValue[] = $idMap[(int) $item];
                }
            }

            return $this->setValue($properties, $path, $newValue);
        }

        if (!$this->isId($value)) {
            return $properties;
        }

        return $this->setValue($properties, $path, $idMap[(int) $value] ?? null);
    }

    private function isId(mixed $value): bool
    {
        return (is_int($value) || is_string($value)) && is_numeric($value) && (int) $value > 0;
    }

    /**
     * @return list<int>
     */
    private function toIds(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_map(intval(...), array_filter($values, $this->isId(...))));
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function getValue(array $properties, string $path): mixed
    {
        foreach (explode('.', $path) as $key) {
            if (!is_array($properties) || !array_key_exists($key, $properties)) {
                return null;
            }
            $properties = $properties[$key];
        }

        return $properties;
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    private function setValue(array $properties, string $path, mixed $value): array
    {
        $keys = explode('.', $path);
        $key  = array_shift($keys);

        $properties[$key] = [] === $keys ? $value : $this->setValue(is_array($properties[$key] ?? null) ? $properties[$key] : [], implode('.', $keys), $value);

        return $properties;
    }
}
