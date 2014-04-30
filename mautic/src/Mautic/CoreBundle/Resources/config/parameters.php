<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$parameters = array(
    "db_driver"             => "pdo_mysql",
    "db_host"               => "localhost",
    "db_port"               => null,
    "db_name"               => "",
    "db_user"               => "",
    "db_password"           => "",
    "db_table_prefix"       => "",
    "mailer_transport"      => "mail",
    "mailer_host"           => "",
    "mailer_user"           => null,
    "mailer_password"       => null,
    "locale"                => "en_US",
    "secret"                => "",
    "trusted_hosts"         => null,
    "trusted_proxies"       => null,
    'rememberme_key'        => '',
    'rememberme_lifetime'   => 31536000, //365 days in seconds
    'rememberme_path'       => '/',
    'rememberme_domain'     => '',
    "default_pagelimit"     => 30
);

return $parameters;