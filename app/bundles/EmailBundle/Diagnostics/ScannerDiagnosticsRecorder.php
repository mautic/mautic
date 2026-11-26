<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Diagnostics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\PageBundle\Entity\Redirect;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ScannerDiagnosticsRecorder
{
   
    public const HEADER_ALLOW_LIST = [
        'Accept',
        'Accept-Language',
        'Sec-Fetch-Site',
        'Sec-Fetch-Mode',
        'Sec-Fetch-Dest',
        'Sec-Fetch-User',
        'Sec-CH-UA',
        'Sec-CH-UA-Mobile',
        'Sec-CH-UA-Platform',
        'Purpose',
        'Sec-Purpose',
        'Upgrade-Insecure-Requests',
        'Via',
    ];

    private const EVENT_TYPES = [
        ScannerDiagnostic::EVENT_HONEYPOT,
        ScannerDiagnostic::EVENT_CLICK,
        ScannerDiagnostic::EVENT_OPEN,
    ];

    private const MAX_VALUE_LENGTH = 255;

    private const HASH_LENGTH = 16;

    private const IPV4_PREFIX_BITS = 24;

    private const IPV6_PREFIX_BITS = 48;

    public function __construct(
        private readonly Connection $connection,
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly IpLookupHelper $ipLookupHelper,
        private readonly BotRatioHelper $botRatioHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('email_scanner_diagnostics_enabled');
    }

    public function record(string $eventType, Request $request, ?Stat $stat, ?Redirect $redirect = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->insert($eventType, $request, $stat, $redirect);
        } catch (\Throwable $exception) {
            $this->logger->warning('Email scanner diagnostics could not be recorded: '.$exception->getMessage(), [
                'event_type' => $eventType,
                'exception'  => $exception,
            ]);
        }
    }

    private function insert(string $eventType, Request $request, ?Stat $stat, ?Redirect $redirect): void
    {
        if (!in_array($eventType, self::EVENT_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown event type "%s"', $eventType));
        }


        if (!$this->ipLookupHelper->isRequestTrackable()
            || $this->ipLookupHelper->isVisitorUntracked()
            || $this->coreParametersHelper->get('anonymize_ip')
        ) {
            return;
        }

        $now       = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $ip        = $this->ipLookupHelper->getIpAddressFromRequest();
        $userAgent = (string) $request->headers->get('User-Agent', '');
        $dateSent  = $stat?->getDateSent();

        $this->connection->insert(
            MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME,
            [
                'date_added'         => $now,
                'event_type'         => $eventType,
                'email_id'           => $stat?->getEmail()?->getId(),
                'send_key'           => $this->getSendKey($stat),
                'redirect_id'        => $redirect?->getId(),
                'seconds_after_send' => null === $dateSent ? null : max(0, $now->getTimestamp() - $dateSent->getTimestamp()),
                'ip_hash'            => $this->hash('ip', $ip),
                'ip_network'         => $this->getNetwork($ip),
                'user_agent'         => '' === $userAgent ? null : mb_substr($userAgent, 0, self::MAX_VALUE_LENGTH),
                'headers'            => $this->getHeaders($request),
                'is_https'           => $request->isSecure(),
                'bot_ratio_flagged'  => null === $stat || null === $dateSent
                    ? null
                    : $this->botRatioHelper->isHitByBot($stat, $now, new IpAddress($ip), $userAgent),
            ],
            [
                'date_added'        => Types::DATETIME_IMMUTABLE,
                'headers'           => Types::JSON,
                'is_https'          => Types::BOOLEAN,
                'bot_ratio_flagged' => Types::BOOLEAN,
            ]
        );
    }

    private function getSendKey(?Stat $stat): ?string
    {
        $trackingHash = $stat?->getTrackingHash();
        return null === $trackingHash || '' === $trackingHash ? null : $this->hash('send', $trackingHash);
    }

    private function hash(string $purpose, string $value): string
    {
        return substr(hash_hmac('sha256', $purpose.':'.$value, (string) $this->coreParametersHelper->get('secret_key')), 0, self::HASH_LENGTH);
    }

    private function getNetwork(string $ip): ?string
    {
        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = (string) inet_pton($ip);
        $bits   = 4 === strlen($packed) ? self::IPV4_PREFIX_BITS : self::IPV6_PREFIX_BITS;
        $prefix = substr($packed, 0, intdiv($bits, 8)).str_repeat("\0", strlen($packed) - intdiv($bits, 8));

        return inet_ntop($prefix).'/'.$bits;
    }

 
    private function getHeaders(Request $request): array
    {
        $headers = [];

        foreach (self::HEADER_ALLOW_LIST as $name) {
            $value = $request->headers->get($name);

            if (null !== $value) {
                $headers[strtolower($name)] = mb_substr($value, 0, self::MAX_VALUE_LENGTH);
            }
        }

        return $headers;
    }
}
