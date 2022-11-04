<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\PRedisConnectionHelper;
use Mautic\CoreBundle\Predis\Replication\MasterOnlyStrategy;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Connection\Aggregate\SentinelReplication;

class PRedisConnectionHelperTest extends TestCase
{
    public function testEndpointsArrayInput(): void
    {
        $a = ['tcp://1.1.1.1', 'unix://var/socket'];
        // assume arrays are already in correct format
        Assert::assertSame($a, PRedisConnectionHelper::getRedisEndpoints($a));
    }

    public function testEndpointsStringInput(): void
    {
        // non domain string should be encapsulated into an array
        Assert::assertSame([['scheme'=>'tcp', 'host'=>'1.1.1.1']], PRedisConnectionHelper::getRedisEndpoints('tcp://1.1.1.1'));

        // domain should be resolved and an array of ip addresses returned
        $connInfo = PRedisConnectionHelper::getRedisEndpoints('tcp://mautic.net:8888?test=car');
        Assert::assertIsArray($connInfo);
        Assert::assertGreaterThan(1, count($connInfo));
        foreach ($connInfo as $c) {
            Assert::assertMatchesRegularExpression('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $c['host']);
            Assert::assertSame('tcp', $c['scheme']);
            Assert::assertSame(8888, $c['port']);
            Assert::assertSame('test=car', $c['query']);
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
        Assert::assertSame($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));

        $result['prefix'] = 'prf:';
        Assert::assertEquals($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration, 'prf:'));

        $redisConfiguration = [
            'password' => 'secretpass',
        ];
        $result = [
            'parameters' => ['password' => 'secretpass'],
        ];
        Assert::assertSame($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));
    }

    public function testCreateClientWithoutSentinel(): void
    {
        $prefix  = 'somePrefix';
        $client  = PRedisConnectionHelper::createClient(['tcp://1.1.1.1'], ['prefix' => $prefix]);
        $options = $client->getOptions();

        \assert($options->prefix instanceof KeyPrefixProcessor);
        Assert::assertSame($prefix, $options->prefix->getPrefix());
        Assert::assertNull($options->aggregate);
    }

    public function testCreateClientWithSentinel(): void
    {
        $prefix  = 'somePrefix';
        $client  = PRedisConnectionHelper::createClient(['tcp://1.1.1.1'], ['prefix' => $prefix, 'replication' => 'sentinel']);
        $options = $client->getOptions();

        \assert($options->prefix instanceof KeyPrefixProcessor);
        Assert::assertSame($prefix, $options->prefix->getPrefix());
        Assert::assertIsCallable($options->aggregate);

        $sentinelReplication = ($options->aggregate)(['tcp://1.1.1.1'], $options);
        Assert::assertInstanceOf(SentinelReplication::class, $sentinelReplication);
        Assert::assertInstanceOf(MasterOnlyStrategy::class, $sentinelReplication->getReplicationStrategy());
    }
}
