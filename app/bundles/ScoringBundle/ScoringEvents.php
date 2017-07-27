<?php
namespace Mautic\ScoringBundle;

/**
 * Description of ScoringEvents
 *
 * @author captivea-qch
 */
final class ScoringEvents {
    /**
     * The mautic.scoringcategory_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_PRE_SAVE = 'mautic.scoringcategory_pre_save';

    /**
     * The mautic.scoringcategory_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_POST_SAVE = 'mautic.scoringcategory_post_save';

    /**
     * The mautic.scoringcategory_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_PRE_DELETE = 'mautic.scoringcategory_pre_delete';

    /**
     * The mautic.scoringcategory_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_POST_DELETE = 'mautic.scoringcategory_post_delete';

    /**
     * The mautic.scoringcategory_on_build event is thrown before displaying the scoring builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryBuilderEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_ON_BUILD = 'mautic.scoringcategory_on_build';

    /**
     * The mautic.scoringcategory_on_action event is thrown to execute a scoring action.
     *
     * The event listener receives a Mautic\ScoringBundle\Event\ScoringCategoryActionEvent instance.
     *
     * @var string
     */
    const SCORINGCATEGORY_ON_ACTION = 'mautic.scoringcategory_on_action';
}
