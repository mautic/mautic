<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventCollector\Builder;

use Mautic\CampaignBundle\Entity\Event;

class ConnectionBuilder
{
    /**
     * @var array
     */
    private static $eventTypes = [];

    /**
     * @var array
     */
    private static $connectionRestrictions = ['anchor' => []];

    /**
     * Used by JS/JsPlumb to restrict how events can be associated to each other in the UI.
     *
     * @param array $events
     *
     * @return array
     */
    public static function buildRestrictionsArray(array $events)
    {
        // Reset restrictions
        self::$connectionRestrictions = ['anchor' => []];

        // Build the restrictions
        self::$eventTypes = array_fill_keys(array_keys($events), []);
        foreach ($events as $eventType => $typeEvents) {
            foreach ($typeEvents as $key => $event) {
                self::addTypeConnection($eventType, $key, $event);
            }
        }

        return self::$connectionRestrictions;
    }

    /**
     * @param string $eventType
     * @param string $key
     * @param array  $event
     */
    private static function addTypeConnection($eventType, $key, array $event)
    {
        if (!isset(self::$connectionRestrictions[$key])) {
            self::$connectionRestrictions[$key] = [
                'source' => self::$eventTypes,
                'target' => self::$eventTypes,
            ];
        }

        if (!isset($connectionRestrictions[$key])) {
            $connectionRestrictions['anchor'][$key] = [];
        }

        if (isset($event['connectionRestrictions'])) {
            foreach ($event['connectionRestrictions'] as $restrictionType => $restrictions) {
                self::addRestriction($key, $restrictionType, $restrictions);
            }
        }

        self::addDeprecatedAnchorRestrictions($eventType, $key, $event);
    }

    /**
     * @param string $key
     * @param string $restrictionType
     * @param array  $restrictions
     */
    private static function addRestriction($key, $restrictionType, array $restrictions)
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
                    list($group, $anchor)                                           = explode('.', $anchor);
                    self::$connectionRestrictions[$restrictionType][$group][$key][] = $anchor;
                }

                break;
        }
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0; BC support
     *
     * @param string $eventType
     * @param string $key
     * @param array  $event
     */
    private static function addDeprecatedAnchorRestrictions($eventType, $key, array $event)
    {
        switch ($eventType) {
            case Event::TYPE_DECISION:
                if (isset($event['associatedActions'])) {
                    self::$connectionRestrictions[$key]['target']['action'] += $event['associatedActions'];
                }
                break;
            case Event::TYPE_ACTION:
                if (isset($event['associatedDecisions'])) {
                    self::$connectionRestrictions[$key]['source']['decision'] += $event['associatedDecisions'];
                }
                break;
        }

        if (isset($event['anchorRestrictions'])) {
            foreach ($event['anchorRestrictions'] as $restriction) {
                list($group, $anchor)                                   = explode('.', $restriction);
                self::$connectionRestrictions['anchor'][$key][$group][] = $anchor;
            }
        }
    }
}
