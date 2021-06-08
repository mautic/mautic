<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle;

/**
 * Class StageEvents.
 *
 * Events available for StageBundle
 */
final class StageEvents
{
    /**
     * The mautic.stage_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageEvent instance.
     *
     * @var string
     */
    const STAGE_PRE_SAVE = 'mautic.stage_pre_save';

    /**
     * The mautic.stage_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageEvent instance.
     *
     * @var string
     */
    const STAGE_POST_SAVE = 'mautic.stage_post_save';

    /**
     * The mautic.stage_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageEvent instance.
     *
     * @var string
     */
    const STAGE_PRE_DELETE = 'mautic.stage_pre_delete';

    /**
     * The mautic.stage_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageEvent instance.
     *
     * @var string
     */
    const STAGE_POST_DELETE = 'mautic.stage_post_delete';

    /**
     * The mautic.stage_on_build event is thrown before displaying the stage builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageBuilderEvent instance.
     *
     * @var string
     */
    const STAGE_ON_BUILD = 'mautic.stage_on_build';

    /**
     * The mautic.stage_on_action event is thrown to execute a stage action.
     *
     * The event listener receives a Mautic\StageBundle\Event\StageActionEvent instance.
     *
     * @var string
     */
    const STAGE_ON_ACTION = 'mautic.stage_on_action';

    /**
     * The mautic.stage.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_BATCH_ACTION = 'mautic.stage.on_campaign_batch_action';

    /**
     * @deprecated; use ON_CAMPAIGN_BATCH_ACTION instead
     *
     * The mautic.stage.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.stage.on_campaign_trigger_action';
}
