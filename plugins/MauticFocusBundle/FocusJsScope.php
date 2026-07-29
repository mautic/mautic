<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle;

enum FocusJsScope
{
    case RUNTIME;
    case DISPLAY;
    case TRACKING;
}
