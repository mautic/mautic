<?php

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
