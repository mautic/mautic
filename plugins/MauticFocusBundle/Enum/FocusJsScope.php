<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Enum;

enum FocusJsScope
{
    case RUNTIME;
    case DISPLAY;
    case TRACKING;
}
