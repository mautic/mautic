<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

final class Type
{
    public const AUTOREPLY    = 'autoreply';

    public const BLOCKED      = 'blocked';

    public const HARD         = 'hard';

    public const GENERIC      = 'generic';

    public const UNKNOWN      = 'unknown';

    public const UNRECOGNIZED = 'unrecognized';

    public const SOFT         = 'soft';

    public const TEMPORARY    = 'temporary';
}
