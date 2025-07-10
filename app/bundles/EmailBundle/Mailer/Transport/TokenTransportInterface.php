<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Transport;

use Symfony\Component\Mime\Email;

interface TokenTransportInterface
{
    /**
     * Return the max number of to addresses allowed per batch.  If there is no limit, return 0.
     */
    public function getMaxBatchLimit(): int;

    /**
     * Get the count for the max number of recipients per batch.
     *
     * @param int    $toBeAdded Number of emails about to be added
     * @param string $type      Type of emails being added (to, cc, bcc)
     */
    public function getBatchRecipientCount(Email $message, int $toBeAdded = 1, string $type = 'to'): int;
}
