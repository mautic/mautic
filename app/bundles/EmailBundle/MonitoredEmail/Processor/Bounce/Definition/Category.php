<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition;

final class Category
{
    /**
     * Message rejected due to spam or anti-abuse filters (e.g., sender blocked, spam detected, blacklists).
     */
    public const string ANTISPAM       = 'antispam';

    /**
     * Message is an auto-reply (e.g., out of office, vacation response).
     */
    public const string AUTOREPLY      = 'autoreply';

    /**
     * Concurrent delivery issues (e.g., too many connections or sessions).
     */
    public const string CONCURRENT     = 'concurrent';

    /**
     * Message rejected due to content issues (e.g., invalid MIME, message structure, or content policy).
     */
    public const string CONTENT_REJECT = 'content_reject';

    /**
     * Message rejected due to command or protocol errors (e.g., relay not permitted, authentication failed).
     */
    public const string COMMAND_REJECT = 'command_reject';

    /**
     * Internal server error or misconfiguration (e.g., I/O error, system config error).
     */
    public const string INTERNAL_ERROR = 'internal_error';

    /**
     * Temporary delivery failure, message may be retried (e.g., system busy, resources unavailable).
     */
    public const string DEFER          = 'defer';

    /**
     * Delivery delayed, message not yet permanently failed (e.g., delivery temporarily suspended).
     */
    public const string DELAYED        = 'delayed';

    /**
     * DNS configuration loop detected (e.g., MX points back to sender, mail loop).
     */
    public const string DNS_LOOP       = 'dns_loop';

    /**
     * DNS or domain-related failure (e.g., host unknown, domain not found, no route to host).
     */
    public const string DNS_UNKNOWN    = 'dns_unknown';

    /**
     * Recipient's mailbox is full or over quota.
     */
    public const string FULL           = 'full';

    /**
     * Recipient account is inactive, suspended, expired, or closed due to inactivity.
     */
    public const string INACTIVE       = 'inactive';

    /**
     * Message rejected due to non-Latin characters or encoding issues.
     */
    public const string LATIN_ONLY     = 'latin_only';

    /**
     * Other or uncategorized bounce reason.
     */
    public const string OTHER          = 'other';

    /**
     * Message rejected due to size limits (e.g., message too large, exceeds system limit).
     */
    public const string OVERSIZE       = 'oversize';

    /**
     * Out of office or auto-reply.
     */
    public const string OUTOFOFFICE    = 'outofoffice';

    /**
     * Unknown recipient or address (e.g., user unknown, invalid address, not listed).
     */
    public const string UNKNOWN        = 'unknown';

    /**
     * Bounce reason could not be recognized or parsed.
     */
    public const string UNRECOGNIZED   = 'unrecognized';

    /**
     * Message rejected by recipient (e.g., user refused, sender not allowed).
     */
    public const string USER_REJECT    = 'user_reject';

    /**
     * Warning or non-fatal issue (e.g., soft bounce, warning notification).
     */
    public const string WARNING        = 'warning';
}
