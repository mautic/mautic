<?php

namespace MauticPlugin\MauticFocusBundle;

class FocusEventTypes
{
    /**
     * The focus.on_open event type is used for event dispatched when an focus is opened.
     *
     * @var string
     */
    public const FOCUS_ON_VIEW = 'focus.on_view';

    /**
     * The focus.on_click event type is used for event dispatched when an focus is clicked.
     *
     * @var string
     */
    public const FOCUS_ON_CLICK = 'focus.on_click';
}
