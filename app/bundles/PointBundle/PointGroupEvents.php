<?php

namespace Mautic\PointBundle;

final class PointGroupEvents
{
    /**
     * The mautic.group_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const GROUP_PRE_SAVE = 'mautic.group_pre_save';

    /**
     * The mautic.group_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const GROUP_POST_SAVE = 'mautic.group_post_save';

    /**
     * The mautic.group_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const GROUP_PRE_DELETE = 'mautic.group_pre_delete';

    /**
     * The mautic.group_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const GROUP_POST_DELETE = 'mautic.group_post_delete';

    /**
     * The mautic.group_contact_score_change event is dispatched if a group contact score changes.
     *
     * The event listener receives a Mautic\PointBundle\Event\GroupScoreChangeEvent instance.
     *
     * @var string
     */
    public const SCORE_CHANGE = 'mautic.group_contact_score_change';
}
