<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$parameters = array(
    'theme'                 => 'Mauve',
    'db_driver'             => 'pdo_mysql',
    'db_host'               => 'localhost',
    'db_port'               => null,
    'db_name'               => '',
    'db_user'               => '',
    'db_password'           => '',
    'db_table_prefix'       => '',
    'mailer_transport'      => 'mail',
    'mailer_host'           => '',
    'mailer_user'           => null,
    'mailer_password'       => null,
    'locale'                => 'en_US',
    'secret'                => '',
    'trusted_hosts'         => null,
    'trusted_proxies'       => null,
    'rememberme_key'        => uniqid(),
    'rememberme_lifetime'   => 31536000, //365 days in seconds
    'rememberme_path'       => '/',
    'rememberme_domain'     => '',
    'default_pagelimit'     => 30,
    'default_timezone'      => 'UTC',
    'date_format_full'      => 'F j, Y g:i a T',
    'date_format_short'     => 'D, M d',
    'date_format_dateonly'  => 'F j, Y',
    'date_format_timeonly'  => 'g:i a',
    'ip_lookup_service'     => 'telize',
        //telize (free with no limit at this time)
        //freegeoip (free with 10000/hr limit)
        //geobytes ( free 20/hr limit or paid account restricted to calls from single IP
        //ipinfodb (paid; api key required)
        //geoips (paid; api key required)
        //maxmind_country, maxmind_precision, or maxmind_omni (paid; username/license key required)
    'ip_lookup_auth'        => ''
);

return $parameters;