<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle;

final class FocusEventTypes
{
    /**
     * The focus.on_open event type is used for event dispatched when an focus is opened.
     */
    public const string FOCUS_ON_VIEW = 'focus.on_view';

    /**
     * The focus.on_click event type is used for event dispatched when an focus is clicked.
     */
    public const string FOCUS_ON_CLICK = 'focus.on_click';
}
