<?php

declare(strict_types=1);

namespace Mautic\PointBundle;

/**
 * Events available for PointBundle.
 */
final class PointEvents
{
    /**
     * The mautic.point_on_build event is thrown before displaying the point builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\PointBundle\Event\PointBuilderEvent instance.
     */
    public const string POINT_ON_BUILD = 'mautic.point_on_build';

    /**
     * The mautic.point_on_action event is thrown to execute a point action.
     *
     * The event listener receives a Mautic\PointBundle\Event\PointActionEvent instance.
     */
    public const string POINT_ON_ACTION = 'mautic.point_on_action';

    /**
     * The mautic.trigger_on_build event is thrown before displaying the trigger builder form to allow adding of custom actions.
     *
     * The event listener receives a Mautic\PointBundle\Event\TriggerBuilderEvent instance.
     */
    public const string TRIGGER_ON_BUILD = 'mautic.trigger_on_build';

    /**
     * The mautic.trigger_on_event_execute event is thrown to execute a trigger event.
     *
     * The event listener receives a Mautic\PointBundle\Event\TriggerExecutedEvent instance.
     */
    public const string TRIGGER_ON_EVENT_EXECUTE = 'mautic.trigger_on_event_execute';

    /**
     * The mautic.trigger_on_lead_segments_change event is thrown to change lead's segments.
     *
     * The event listener receives a Mautic\PointBundle\Event\TriggerExecutedEvent instance.
     */
    public const string TRIGGER_ON_LEAD_SEGMENTS_CHANGE = 'mautic.trigger_on_lead_segments_change';
}
