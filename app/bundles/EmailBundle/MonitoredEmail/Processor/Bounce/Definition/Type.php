<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

final class Type
{
    public const string AUTOREPLY    = 'autoreply';

    public const string BLOCKED      = 'blocked';

    public const string HARD         = 'hard';

    public const string GENERIC      = 'generic';

    public const string UNKNOWN      = 'unknown';

    public const string UNRECOGNIZED = 'unrecognized';

    public const string SOFT         = 'soft';

    public const string TEMPORARY    = 'temporary';
}
