<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

final class Category
{
    /**
     * Message rejected due to spam or anti-abuse filters (e.g., sender blocked, spam detected, blacklists).
     */
    public const ANTISPAM       = 'antispam';

    /**
     * Message is an auto-reply (e.g., out of office, vacation response).
     */
    public const AUTOREPLY      = 'autoreply';

    /**
     * Concurrent delivery issues (e.g., too many connections or sessions).
     */
    public const CONCURRENT     = 'concurrent';

    /**
     * Message rejected due to content issues (e.g., invalid MIME, message structure, or content policy).
     */
    public const CONTENT_REJECT = 'content_reject';

    /**
     * Message rejected due to command or protocol errors (e.g., relay not permitted, authentication failed).
     */
    public const COMMAND_REJECT = 'command_reject';

    /**
     * Internal server error or misconfiguration (e.g., I/O error, system config error).
     */
    public const INTERNAL_ERROR = 'internal_error';

    /**
     * Temporary delivery failure, message may be retried (e.g., system busy, resources unavailable).
     */
    public const DEFER          = 'defer';

    /**
     * Delivery delayed, message not yet permanently failed (e.g., delivery temporarily suspended).
     */
    public const DELAYED        = 'delayed';

    /**
     * DNS configuration loop detected (e.g., MX points back to sender, mail loop).
     */
    public const DNS_LOOP       = 'dns_loop';

    /**
     * DNS or domain-related failure (e.g., host unknown, domain not found, no route to host).
     */
    public const DNS_UNKNOWN    = 'dns_unknown';

    /**
     * Recipient's mailbox is full or over quota.
     */
    public const FULL           = 'full';

    /**
     * Recipient account is inactive, suspended, expired, or closed due to inactivity.
     */
    public const INACTIVE       = 'inactive';

    /**
     * Message rejected due to non-Latin characters or encoding issues.
     */
    public const LATIN_ONLY     = 'latin_only';

    /**
     * Other or uncategorized bounce reason.
     */
    public const OTHER          = 'other';

    /**
     * Message rejected due to size limits (e.g., message too large, exceeds system limit).
     */
    public const OVERSIZE       = 'oversize';

    /**
     * Out of office or auto-reply.
     */
    public const OUTOFOFFICE    = 'outofoffice';

    /**
     * Unknown recipient or address (e.g., user unknown, invalid address, not listed).
     */
    public const UNKNOWN        = 'unknown';

    /**
     * Bounce reason could not be recognized or parsed.
     */
    public const UNRECOGNIZED   = 'unrecognized';

    /**
     * Message rejected by recipient (e.g., user refused, sender not allowed).
     */
    public const USER_REJECT    = 'user_reject';

    /**
     * Warning or non-fatal issue (e.g., soft bounce, warning notification).
     */
    public const WARNING        = 'warning';
}
