<?php

declare(strict_types=1);

namespace Mautic\PointBundle;

final class PointGroupEvents
{
    /**
     * The mautic.group_contact_score_change event is dispatched if a group contact score changes.
     *
     * The event listener receives a Mautic\PointBundle\Event\GroupScoreChangeEvent instance.
     */
    public const string SCORE_CHANGE = 'mautic.group_contact_score_change';
}
