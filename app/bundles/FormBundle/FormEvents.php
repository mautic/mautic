<?php

namespace Mautic\FormBundle;

/**
 * Class FormEvents.
 *
 * Events available for FormBundle
 */
final class FormEvents
{
    /**
     * The mautic.form_pre_save event is dispatched right before a form is persisted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_PRE_SAVE = 'mautic.form_pre_save';

    /**
     * The mautic.form_post_save event is dispatched right after a form is persisted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_POST_SAVE = 'mautic.form_post_save';

    /**
     * The mautic.form_pre_delete event is dispatched before a form is deleted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_PRE_DELETE = 'mautic.form_pre_delete';

    /**
     * The mautic.form_post_delete event is dispatched after a form is deleted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_POST_DELETE = 'mautic.form_post_delete';

    /**
     * The mautic.field_pre_save event is dispatched right before a field is persisted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormFieldEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_SAVE = 'mautic.field_pre_save';

    /**
     * The mautic.field_post_save event is dispatched right after a field is persisted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_SAVE = 'mautic.field_post_save';

    /**
     * The mautic.field_pre_delete event is dispatched before a field is deleted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormFieldEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_DELETE = 'mautic.field_pre_delete';

    /**
     * The mautic.field_post_delete event is dispatched after a field is deleted.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_DELETE = 'mautic.field_post_delete';

    /**
     * The mautic.form_on_build event is dispatched before displaying the form builder form to allow adding of custom form
     * fields and submit actions.
     *
     * The event listener receives a Mautic\FormBundle\Event\FormBuilderEvent instance.
     *
     * @var string
     */
    const FORM_ON_BUILD = 'mautic.form_on_build';

    /**
     * The mautic.on_form_validate event is dispatched when a form is validated.
     *
     * The event listener receives a Mautic\FormBundle\Event\ValidationEvent instance.
     *
     * @var string
     */
    const ON_FORM_VALIDATE = 'mautic.on_form_validate';

    /**
     * The mautic.form_on_submit event is dispatched when a new submission is fired.
     *
     * The event listener receives a Mautic\FormBundle\Event\SubmissionEvent instance.
     *
     * @var string
     */
    const FORM_ON_SUBMIT = 'mautic.form_on_submit';

    /**
     * The mautic.form.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.form.on_campaign_trigger_condition';

    /**
     * The mautic.form.on_campaign_trigger_decision event is fired when the campaign decision triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.form.on_campaign_trigger_decision';

    /**
     * The mautic.form.on_execute_submit_action event is dispatched to excecute the form submit actions.
     *
     * The event listener receives a
     * Mautic\FormBundle\Event\SubmissionEvent
     *
     * @var string
     */
    const ON_EXECUTE_SUBMIT_ACTION = 'mautic.form.on_execute_submit_action';

    /**
     * The mautic.form.on_submission_rate_winner event is fired when there is a need to determine submission rate winner.
     *
     * The event listener receives a
     * Mautic\CoreBundles\Event\DetermineWinnerEvent
     *
     * @var string
     */
    const ON_DETERMINE_SUBMISSION_RATE_WINNER = 'mautic.form.on_submission_rate_winner';
}
