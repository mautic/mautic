<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Logger;


use Psr\Log\LoggerInterface;

class DebugLogger
{
    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * SyncLogger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        static::$logger = $logger;
    }

    /**
     * @param string $integration
     * @param string $loggedFrom
     * @param string $message
     * @param array  $context
     */
    public static function log($integration, $message, $loggedFrom = null, array $context = [], $urgency = 'debug')
    {
        if (!static::$logger) {
            return;
        }

        if (null !== $loggedFrom) {
            $context['logged from'] = $loggedFrom;
        }

        static::$logger->$urgency(strtoupper($integration)." SYNC: ".$message, $context);
    }
}