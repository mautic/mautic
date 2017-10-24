<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
