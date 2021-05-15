<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle;

final class StatEvents
{
    /**
     * The mautic.aggregate_stat_request event is dispatched when an aggregate stat is requested.
     *
     * The event listener receives a \Mautic\StatsBundle\Event\AggregateStatRequestEvent instance.
     *
     * @var string
     */
    const AGGREGATE_STAT_REQUEST = 'mautic.aggregate_stat_request';
}
