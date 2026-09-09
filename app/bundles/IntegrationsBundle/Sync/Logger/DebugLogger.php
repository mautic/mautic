<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class DebugLogger
{
    private static ?LoggerInterface $logger = null;

    public function __construct(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    /**
     * @param string               $integration
     * @param string               $loggedFrom
     * @param string               $message
     * @param string               $urgency
     * @param array<string, mixed> $context
     */
    public static function log($integration, $message, $loggedFrom = null, array $context = [], $urgency = LogLevel::DEBUG): void
    {
        if (!self::$logger) {
            return;
        }

        if (null !== $loggedFrom) {
            $context['logged from'] = $loggedFrom;
        }

        self::$logger->{$urgency}(strtoupper($integration).' SYNC: '.$message, $context);
    }
}
