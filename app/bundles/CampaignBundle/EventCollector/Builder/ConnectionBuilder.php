<?php

namespace Mautic\CampaignBundle\EventCollector\Builder;

final class ConnectionBuilder
{
    private static array $eventTypes = [];

    /**
     * @var array<string, mixed>
     */
    private static array $connectionRestrictions = ['anchor' => []];

    /**
     * Used by JS/JsPlumb to restrict how events can be associated to each other in the UI.
     */
    public static function buildRestrictionsArray(array $events): array
    {
        // Reset restrictions
        self::$connectionRestrictions = ['anchor' => []];

        // Build the restrictions
        self::$eventTypes = array_fill_keys(array_keys($events), []);
        foreach ($events as $typeEvents) {
            foreach ($typeEvents as $key => $event) {
                self::addTypeConnection($key, $event);
            }
        }

        return self::$connectionRestrictions;
    }

    /**
     * @param array<string, mixed> $event
     */
    private static function addTypeConnection(string $key, array $event): void
    {
        self::$connectionRestrictions[$key] ??= [
            'source' => self::$eventTypes,
            'target' => self::$eventTypes,
        ];

        if (isset($event['connectionRestrictions'])) {
            foreach ($event['connectionRestrictions'] as $restrictionType => $restrictions) {
                self::addRestriction($key, $restrictionType, $restrictions);
            }
        }
    }

    /**
     * @param string $restrictionType
     */
    private static function addRestriction(string $key, $restrictionType, array $restrictions): void
    {
        switch ($restrictionType) {
            case 'source':
            case 'target':
                foreach ($restrictions as $groupType => $groupRestrictions) {
                    self::$connectionRestrictions[$key][$restrictionType][$groupType] += $groupRestrictions;
                }
                break;
            case 'anchor':
                foreach ($restrictions as $anchor) {
                    [$group, $anchor]                                               = explode('.', $anchor);
                    self::$connectionRestrictions[$restrictionType][$group][$key][] = $anchor;
                }

                break;
        }
    }
}
