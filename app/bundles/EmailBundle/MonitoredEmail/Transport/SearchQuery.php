<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Transport;

/**
 * Preserve the native mailbox API's IMAP search grammar.
 *
 * @internal
 */
final class SearchQuery extends \Horde_Imap_Client_Search_Query
{
    public function __construct(private readonly string $criteria)
    {
        if ('' === trim($criteria) || strpbrk($criteria, "\r\n\0")) {
            throw new \InvalidArgumentException('Invalid IMAP search criteria.');
        }
    }

    /** @param mixed $exts */
    public function build($exts = []): array
    {
        return [
            'charset' => null,
            'exts'    => [],
            'query'   => new \Horde_Imap_Client_Data_Format_List([new \Horde_Imap_Client_Data_Format($this->criteria)]),
        ];
    }
}
