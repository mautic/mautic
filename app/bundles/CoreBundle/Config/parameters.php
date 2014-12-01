<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$parameters = array(
    'site_url'                     => '',
    'send_server_data'             => false,
    'cache_path'                   => '%kernel.root_dir%/cache',
    'log_path'                     => '%kernel.root_dir%/logs',
    'theme'                        => 'Mauve',
    'db_driver'                    => 'mysqli',
    'db_host'                      => 'localhost',
    'db_port'                      => 3306,
    'db_name'                      => '',
    'db_user'                      => '',
    'db_password'                  => '',
    'db_table_prefix'              => '',
    'mailer_from_name'             => 'Mautic',
    'mailer_from_email'            => 'email@yoursite.com',
    'mailer_transport'             => 'mail',
    'mailer_host'                  => '',
    'mailer_port'                  => null,
    'mailer_user'                  => null,
    'mailer_password'              => null,
    'mailer_encryption'            => null, //tls or ssl,
    'mailer_auth_mode'             => null, //plain, login or cram-md5
    'mailer_spool_type'            => 'file', //memory will send immediately
    'mailer_spool_path'            => '%kernel.root_dir%/spool',
    'mailer_spool_msg_limit'       => null,
    'mailer_spool_time_limit'      => null,
    'mailer_spool_recover_timeout' => 900,
    'mailer_spool_clear_timeout'   => 1800,
    'locale'                       => 'en_US',
    'secret'                       => '',
    'trusted_hosts'                => null,
    'trusted_proxies'              => null,
    'rememberme_key'               => hash('sha1', uniqid(mt_rand())),
    'rememberme_lifetime'          => 31536000, //365 days in seconds
    'rememberme_path'              => '/',
    'rememberme_domain'            => '',
    'default_pagelimit'            => 30,
    'default_timezone'             => 'UTC',
    'date_format_full'             => 'F j, Y g:i a T',
    'date_format_short'            => 'D, M d',
    'date_format_dateonly'         => 'F j, Y',
    'date_format_timeonly'         => 'g:i a',
    'ip_lookup_service'            => 'telize',
    //telize (free with no limit at this time)
    //freegeoip (free with 10000/hr limit)
    //geobytes ( free 20/hr limit or paid account restricted to calls from single IP
    //ipinfodb (paid; api key required)
    //geoips (paid; api key required)
    //maxmind_country, maxmind_precision, or maxmind_omni (paid; username/license key required)
    'ip_lookup_auth'               => '',
    'transifex_username'           => '',
    'transifex_password'           => '',
    'update_stability'             => 'stable',
    'cookie_path'                  => '/',
    'cookie_domain'                => '',
    'cookie_secure'                => null,
    'cookie_httponly'              => false
);

return $parameters;
