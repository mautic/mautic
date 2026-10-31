<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\PRedisConnectionHelper;
use Mautic\CoreBundle\Predis\Command\Unlink;
use Mautic\CoreBundle\Predis\Replication\MasterOnlyStrategy;
use PHPUnit\Framework\TestCase;
use Predis\Cluster\ClusterStrategy;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Connection\Cluster\PredisCluster;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\Replication\SentinelReplication;

final class PRedisConnectionHelperTest extends TestCase
{
    public function testEndpointsArrayInput(): void
    {
        $a = ['tcp://1.1.1.1', 'unix://var/socket'];
        // assume arrays are already in correct format
        $this->assertSame($a, PRedisConnectionHelper::getRedisEndpoints($a));
    }

    public function testEndpointsStringInput(): void
    {
        // non domain string should be encapsulated into an array
        $this->assertSame([['scheme'=>'tcp', 'host'=>'1.1.1.1']], PRedisConnectionHelper::getRedisEndpoints('tcp://1.1.1.1'));

        // domain should be resolved and an array of ip addresses returned
        $connInfo = PRedisConnectionHelper::getRedisEndpoints('tcp://bing.com:8888?test=car');
        $this->assertIsArray($connInfo);
        $this->assertGreaterThan(1, count($connInfo));
        foreach ($connInfo as $c) {
            $this->assertMatchesRegularExpression('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $c['host']);
            $this->assertSame('tcp', $c['scheme']);
            $this->assertSame(8888, $c['port']);
            $this->assertSame('test=car', $c['query']);
        }
    }

    public function testRedisOptions(): void
    {
        $redisConfiguration = [
            'replication' => 'sentinel',
            'service'     => 'secondmaster',
            'password'    => 'secretpass',
        ];
        $result = [
            'replication' => 'sentinel',
            'service'     => 'secondmaster',
            'parameters'  => ['password' => 'secretpass'],
        ];
        $this->assertSame($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));

        // use as first item in array
        $result = ['prefix' => 'prf:'] + $result;
        $this->assertSame($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration, 'prf:'));

        $redisConfiguration = [
            'password' => 'secretpass',
        ];
        $result = [
            'parameters' => ['password' => 'secretpass'],
        ];
        $this->assertSame($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));
    }

    public function testCreateClientWithoutSentinel(): void
    {
        $prefix  = 'somePrefix';
        $client  = PRedisConnectionHelper::createClient(['tcp://1.1.1.1'], ['prefix' => $prefix]);
        $options = $client->getOptions();

        $this->assertInstanceOf(KeyPrefixProcessor::class, $options->prefix);
        $this->assertSame($prefix, $options->prefix->getPrefix());
        $this->assertNull($options->aggregate);

        $commandFactory = $client->getCommandFactory();
        $this->assertTrue($commandFactory->supports(Unlink::ID));

        $connection = $client->getConnection();

        if ($connection instanceof RedisCluster || $connection instanceof PredisCluster) {
            $clusterStrategy = $connection->getClusterStrategy();
            $this->assertInstanceOf(ClusterStrategy::class, $clusterStrategy);

            $this->assertContains(Unlink::ID, $clusterStrategy->getSupportedCommands());
        }
    }

    public function testCreateClientWithSentinel(): void
    {
        $prefix  = 'somePrefix';
        $client  = PRedisConnectionHelper::createClient(['tcp://1.1.1.1'], ['prefix' => $prefix, 'replication' => 'sentinel']);
        $options = $client->getOptions();

        $this->assertInstanceOf(KeyPrefixProcessor::class, $options->prefix);
        $this->assertSame($prefix, $options->prefix->getPrefix());
        $this->assertIsCallable($options->aggregate);

        $sentinelReplication = ($options->aggregate)(['tcp://1.1.1.1'], $options);
        $this->assertInstanceOf(SentinelReplication::class, $sentinelReplication);
        $this->assertInstanceOf(MasterOnlyStrategy::class, $sentinelReplication->getReplicationStrategy());
    }
}
