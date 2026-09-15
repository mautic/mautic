<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Exception;

final class PartialBatchException extends \RuntimeException
{
    private const DEFAULT_FAILURE_STATUS = 'mautic.sms.timeline.event.failed';

    private const DELIVERED_STATUS = 'mautic.sms.timeline.status.delivered';

    private const SAFE_STATUSES = [
        self::DELIVERED_STATUS,
        'mautic.sms.timeline.status.failed',
        'mautic.sms.timeline.status.scheduled',
        'mautic.sms.campaign.failed.missing_number',
        'mautic.sms.campaign.failed.not_contactable',
        'mautic.sms.campaign.failed.unpublished',
        'mautic.sms.config.no_transport',
    ];

    /**
     * @var array<int, array{sent: bool, status: string}>
     */
    private array $results = [];

    /**
     * @param array<int, array<string, mixed>> $results
     */
    public function __construct(array $results, \Throwable $previous)
    {
        parent::__construct('SMS batch transport failed after partial completion.', 0, $previous);

        foreach ($results as $contactId => $result) {
            $sent   = true === ($result['sent'] ?? false);
            $status = $result['status'] ?? null;

            if (!is_string($status) || !in_array($status, self::SAFE_STATUSES, true)) {
                $status = $sent ? self::DELIVERED_STATUS : self::DEFAULT_FAILURE_STATUS;
            }

            $this->results[(int) $contactId] = [
                'sent'   => $sent,
                'status' => $status,
            ];
        }
    }

    /**
     * @return array<int, array{sent: bool, status: string}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Returns class metadata only; structured batch results never include provider messages.
     *
     * @return class-string<\Throwable>
     */
    public function getOriginalExceptionClass(): string
    {
        $previous = $this->getPrevious();
        \assert($previous instanceof \Throwable);

        return $previous::class;
    }
}
