<?php

namespace Mautic\PointBundle;

final class LeagueEvents
{
    /**
     * The mautic.league_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const LEAGUE_PRE_SAVE = 'mautic.league_pre_save';

    /**
     * The mautic.league_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const LEAGUE_POST_SAVE = 'mautic.league_post_save';

    /**
     * The mautic.league_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const LEAGUE_PRE_DELETE = 'mautic.league_pre_delete';

    /**
     * The mautic.league_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    public const LEAGUE_POST_DELETE = 'mautic.league_post_delete';

    /**
     * The mautic.league_on_build event is thrown before displaying the league builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryBuilderEvent instance.
     *
     * @var string
     */
    public const LEAGUE_ON_BUILD = 'mautic.league_on_build';

    /**
     * The mautic.league_on_action event is thrown to execute a league action.
     *
     * The event listener receives a Mautic\PointBundle\Event\ScoringCategoryActionEvent instance.
     *
     * @var string
     */
    public const LEAGUE_ON_ACTION = 'mautic.league_on_action';
}
