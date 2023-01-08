<?php

namespace Mautic\ConfigBundle\Tests\Mapper\Helper;

use Mautic\ConfigBundle\Mapper\Helper\ConfigHelper;

class ConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Ensure a mixed numeric/string keyed array is formatted to all string based keys
     *
     * @covers \Mautic\ConfigBundle\Mapper\Helper\ConfigHelper::bindNestedConfigValues
     */
    public function testNestedLocalParametersAreBoundCorrectly()
    {
        $defaults = [
            'db_host'         => null,
            'db_user'         => null,
            'api_enabled'     => 1,
            'monitored_email' => [
                'general' => [
                    'address'    => null,
                    'host'       => null,
                    'port'       => '993',
                    'encryption' => '/ssl',
                    'user'       => null,
                    'password'   => null,
                ],
                'EmailBundle_bounces' => [
                    'address'           => 'test2@test.com',
                    'host'              => 'test2.com',
                    'port'              => '143',
                    'encryption'        => '/tls/novalidate-cert',
                    'user'              => 'test2@test.com',
                    'password'          => 'password',
                    'override_settings' => 1,
                    'folder'            => 'INBOX',
                ],
                'EmailBundle_unsubscribes' => [
                    'address'           => 'test3@test.com',
                    'host'              => null,
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => null,
                    'password'          => null,
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
            ],
        ];

        $config = [
            'db_host'         => 'dbhost',
            'db_user'         => 'dbuser',
            'monitored_email' => [
                'general' => [
                    'address'    => 'test@test.com',
                    'host'       => 'test.com',
                    'port'       => '143',
                    'encryption' => '/tls/novalidate-cert',
                    'user'       => 'test@test.com',
                    'password'   => 'password',
                ],
                'EmailBundle_bounces'      => null,
                'EmailBundle_unsubscribes' => [
                    'address'           => 'test3@test.com',
                    'host'              => null,
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => null,
                    'password'          => null,
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
                'EmailBundle_replies' => [
                    'address'           => 'test4@test.com',
                    'host'              => null,
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => null,
                    'password'          => null,
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
            ],
        ];

        $expected = [
            // from config
            'db_host' => 'dbhost',
            'db_user' => 'dbuser',
            // from defaults
            'api_enabled'     => 1,
            'monitored_email' => [
                // from config
                'general' => [
                    'address'    => 'test@test.com',
                    'host'       => 'test.com',
                    'port'       => '143',
                    'encryption' => '/tls/novalidate-cert',
                    'user'       => 'test@test.com',
                    'password'   => 'password',
                ],
                'EmailBundle_bounces' => [
                    // from defaults
                    'address'           => 'test2@test.com',
                    'host'              => 'test2.com',
                    'port'              => '143',
                    'encryption'        => '/tls/novalidate-cert',
                    'user'              => 'test2@test.com',
                    'password'          => 'password',
                    'override_settings' => 1,
                    'folder'            => 'INBOX',
                ],
                // from config
                'EmailBundle_unsubscribes' => [
                    'address'           => 'test3@test.com',
                    'host'              => null,
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => null,
                    'password'          => null,
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
                // from config
                'EmailBundle_replies' => [
                    'address'           => 'test4@test.com',
                    'host'              => null,
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => null,
                    'password'          => null,
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
            ],
        ];

        $this->assertEquals($expected, ConfigHelper::bindNestedConfigValues($config, $defaults));
    }
}
