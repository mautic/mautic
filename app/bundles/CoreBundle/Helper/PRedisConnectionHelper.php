<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Predis\Command\Unlink;
use Mautic\CoreBundle\Predis\Replication\MasterOnlyStrategy;
use Mautic\CoreBundle\Predis\Replication\StrategyConfig;
use Predis\Client;
use Predis\Cluster\ClusterStrategy;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\Aggregate\RedisCluster;
use Predis\Connection\Aggregate\SentinelReplication;
use Predis\Profile\RedisProfile;

/**
 * Helper functions for simpler operations with arrays.
 */
class PRedisConnectionHelper
{
    /**
     * Transform the redis url config key into an array if needed
     * If possible also resolve the configured redis hostname to multiple IP addresses.
     *
     * @param mixed $configuredUrls a string or an array of redis endpoints to connect to
     */
    public static function getRedisEndpoints($configuredUrls): iterable
    {
        if (is_iterable($configuredUrls)) {
            // assume arrays are already in the correct format
            return $configuredUrls;
        } else {
            $parsed = parse_url($configuredUrls);
            if (!$parsed) {
                return [$configuredUrls];
            }

            // resolve hostnames ahead of time to support dns records with multiple ip addresses
            // we need to provide each one to predis separately or it will just use a single one
            $resolvedArray = gethostbynamel($parsed['host']);
            if (!$resolvedArray) {
                return [$configuredUrls];
            } else {
                // this will return an array of associative arrays which is supported by Predis
                return array_map(function ($i) use ($parsed) {
                    $parsed['host'] = $i;

                    return $parsed;
                }, $resolvedArray);
            }
        }
    }

    /**
     * Transform the redis mautic config to an options array consumable by PRedis.
     *
     * @param array $redisConfiguration mautic's redis configuration
     */
    public static function makeRedisOptions(array $redisConfiguration, string $prefix = ''): array
    {
        $redisOptions = [];

        if ($prefix) {
            $redisOptions['prefix'] = $prefix;
        }

        if (!empty($redisConfiguration['replication'])) {
            $redisOptions['replication'] = $redisConfiguration['replication'];
            $redisOptions['service']     = $redisConfiguration['service'] ?? 'mymaster';
        }

        if (!empty($redisConfiguration['password'])) {
            $redisOptions['parameters'] = ['password' => $redisConfiguration['password']];
        }

        foreach (['cluster', 'scheme', 'ssl'] as $key) {
            if (isset($redisConfiguration[$key])) {
                $redisOptions[$key] = $redisConfiguration[$key];
            }
        }

        return $redisOptions;
    }

    /**
     * @param mixed[] $endpoints
     * @param mixed[] $inputOptions
     */
    public static function createClient(array $endpoints, array $inputOptions): Client
    {
        $replication = $inputOptions['replication'] ?? null;

        if ('sentinel' === $replication) {
            $inputOptions['aggregate'] = fn () => fn ($sentinels, $options): SentinelReplication => new SentinelReplication(
                $options->service,
                $sentinels,
                $options->connections,
                new MasterOnlyStrategy(StrategyConfig::fromArray($inputOptions))
            );
        }

        $client  = new Client($endpoints, $inputOptions);
        $profile = $client->getProfile();
        \assert($profile instanceof RedisProfile);

        if (!$profile->getCommandClass(Unlink::ID)) {
            $profile->defineCommand(Unlink::ID, Unlink::class);
        }

        $connection = $client->getConnection();

        if ($connection instanceof RedisCluster || $connection instanceof PredisCluster) {
            $clusterStrategy = $connection->getClusterStrategy();

            if ($clusterStrategy instanceof ClusterStrategy && !in_array(Unlink::ID, $clusterStrategy->getSupportedCommands())) {
                $clusterStrategy->setCommandHandler(Unlink::ID, [$clusterStrategy, 'getKeyFromAllArguments']);
            }
        }

        return $client;
    }
}
