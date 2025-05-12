<?php

namespace Mautic\ConfigBundle\Tests\Mapper\Helper;

use Mautic\ConfigBundle\Mapper\Helper\RestrictionHelper;

#[\PHPUnit\Framework\Attributes\CoversClass(RestrictionHelper::class)]
class RestrictionHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $restrictedFields = [
        'db_host',
        'db_user',
        'monitored_email' => [
            'EmailBundle_bounces',
            'EmailBundle_unsubscribes' => [
                'address',
            ],
        ],
    ];

    #[\PHPUnit\Framework\Attributes\TestDox('Ensure a mixed numeric/string keyed array is formatted to all string based keys')]
    public function testRestrictedConfigArrayIsFormattedCorrectly(): void
    {
        $expected = [
            'db_host'         => 'db_host',
            'db_user'         => 'db_user',
            'monitored_email' => [
                'EmailBundle_bounces'      => 'EmailBundle_bounces',
                'EmailBundle_unsubscribes' => [
                    'address' => 'address',
                ],
            ],
        ];

        $this->assertEquals($expected, RestrictionHelper::prepareRestrictions($this->restrictedFields));
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Ensure a restrictions are recursively applied')]
    public function testApplyingRestrictionsToConfigArray(): void
    {
        $config = [
            'db_host'         => 'dbhost',
            'db_user'         => 'dbuser',
            'api_enabled'     => 1,
            'monitored_email' => [
                'general' => [
                    'address'    => 'test@test.com',
                    'host'       => 'test.com',
                    'port'       => '143',
                    'encryption' => '/tls/novalidate-cert',
                    'user'       => 'test@test.com',
                    'password'   => 'password',
                ],
                'EmailBundle_bounces' => [
                    'address'           => '',
                    'host'              => '',
                    'port'              => '993',
                    'encryption'        => '/ssl',
                    'user'              => '',
                    'password'          => '',
                    'override_settings' => 0,
                    'folder'            => 'INBOX',
                ],
                'EmailBundle_unsubscribes' => [
                    'address'           => null,
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
            'api_enabled'     => 1,
            'monitored_email' => [
                'general' => [
                    'address'    => 'test@test.com',
                    'host'       => 'test.com',
                    'port'       => '143',
                    'encryption' => '/tls/novalidate-cert',
                    'user'       => 'test@test.com',
                    'password'   => 'password',
                ],
                'EmailBundle_unsubscribes' => [
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

        $restrictedFields = RestrictionHelper::prepareRestrictions($this->restrictedFields);
        $this->assertEquals($expected, RestrictionHelper::applyRestrictions($config, $restrictedFields));
    }
}
