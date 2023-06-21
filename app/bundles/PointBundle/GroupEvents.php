<?php

namespace Mautic\PointBundle;

final class GroupEvents
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
     * The mautic.group_on_build event is thrown before displaying the group builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryBuilderEvent instance.
     *
     * @var string
     */
    public const GROUP_ON_BUILD = 'mautic.group_on_build';

    /**
     * The mautic.group_on_action event is thrown to execute a group action.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryActionEvent instance.
     *
     * @var string
     */
    public const GROUP_ON_ACTION = 'mautic.group_on_action';
}
