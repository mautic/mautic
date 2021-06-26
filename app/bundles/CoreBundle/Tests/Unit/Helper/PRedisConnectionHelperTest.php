<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\PRedisConnectionHelper;

class PRedisConnectionHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testEndpointsArrayInput(): void
    {
        $a = ['tcp://1.1.1.1', 'unix://var/socket'];
        // assume arrays are already in correct format
        $this->assertEquals($a, PRedisConnectionHelper::getRedisEndpoints($a));
    }

    public function testEndpointsStringInput(): void
    {
        // non domain string should be encapsulated into an array
        $this->assertEquals([['scheme'=>'tcp', 'host'=>'1.1.1.1']], PRedisConnectionHelper::getRedisEndpoints('tcp://1.1.1.1'));

        // domain should be resolved and an array of ip addresses returned
        $connInfo = PRedisConnectionHelper::getRedisEndpoints('tcp://mautic.net:8888?test=car');
        $this->assertIsArray($connInfo);
        $this->assertGreaterThan(1, count($connInfo));
        foreach ($connInfo as $c) {
            $this->assertMatchesRegularExpression('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $c['host']);
            $this->assertEquals('tcp', $c['scheme']);
            $this->assertEquals(8888, $c['port']);
            $this->assertEquals('test=car', $c['query']);
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
        $this->assertEquals($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));

        $result['prefix'] = 'prf:';
        $this->assertEquals($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration, 'prf:'));

        $redisConfiguration = [
            'password' => 'secretpass',
        ];
        $result = [
            'parameters' => ['password' => 'secretpass'],
        ];
        $this->assertEquals($result, PRedisConnectionHelper::makeRedisOptions($redisConfiguration));
    }
}
