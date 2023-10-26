<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DebugLogger
{
    /**
     * @var LoggerInterface
     */
    private static $logger;

    public function __construct(LoggerInterface $logger)
    {
        static::$logger = $logger;
    }

    /**
     * @param string $integration
     * @param string $loggedFrom
     * @param string $message
     * @param string $urgency
     */
    public static function log($integration, $message, $loggedFrom = null, array $context = [], $urgency = LogLevel::DEBUG): void
    {
        if (!static::$logger) {
            return;
        }

        if (null !== $loggedFrom) {
            $context['logged from'] = $loggedFrom;
        }

        static::$logger->$urgency(strtoupper($integration).' SYNC: '.$message, $context);
    }
}
