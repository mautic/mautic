<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

/**
 * Class Type.
 */
final class Type
{
    const AUTOREPLY    = 'autoreply';
    const BLOCKED      = 'blocked';
    const HARD         = 'hard';
    const GENERIC      = 'generic';
    const UNKNOWN      = 'unknown';
    const UNRECOGNIZED = 'unrecognized';
    const SOFT         = 'soft';
    const TEMPORARY    = 'temporary';
}
