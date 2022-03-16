<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

/**
 * Class Category.
 */
final class Category
{
    const ANTISPAM       = 'antispam';
    const AUTOREPLY      = 'autoreply';
    const CONCURRENT     = 'concurrent';
    const CONTENT_REJECT = 'content_reject';
    const COMMAND_REJECT = 'command_reject';
    const INTERNAL_ERROR = 'internal_error';
    const DEFER          = 'defer';
    const DELAYED        = 'delayed';
    const DNS_LOOP       = 'dns_loop';
    const DNS_UNKNOWN    = 'dns_unknown';
    const FULL           = 'full';
    const INACTIVE       = 'inactive';
    const LATIN_ONLY     = 'latin_only';
    const OTHER          = 'other';
    const OVERSIZE       = 'oversize';
    const OUTOFOFFICE    = 'outofoffice';
    const UNKNOWN        = 'unknown';
    const UNRECOGNIZED   = 'unrecognized';
    const USER_REJECT    = 'user_reject';
    const WARNING        = 'warning';
}
