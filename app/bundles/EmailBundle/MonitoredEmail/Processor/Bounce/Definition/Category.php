<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

final class Category
{
    public const ANTISPAM       = 'antispam';

    public const AUTOREPLY      = 'autoreply';

    public const CONCURRENT     = 'concurrent';

    public const CONTENT_REJECT = 'content_reject';

    public const COMMAND_REJECT = 'command_reject';

    public const INTERNAL_ERROR = 'internal_error';

    public const DEFER          = 'defer';

    public const DELAYED        = 'delayed';

    public const DNS_LOOP       = 'dns_loop';

    public const DNS_UNKNOWN    = 'dns_unknown';

    public const FULL           = 'full';

    public const INACTIVE       = 'inactive';

    public const LATIN_ONLY     = 'latin_only';

    public const OTHER          = 'other';

    public const OVERSIZE       = 'oversize';

    public const OUTOFOFFICE    = 'outofoffice';

    public const UNKNOWN        = 'unknown';

    public const UNRECOGNIZED   = 'unrecognized';

    public const USER_REJECT    = 'user_reject';

    public const WARNING        = 'warning';
}
