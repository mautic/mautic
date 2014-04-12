<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$loader->import("config.php");

$container->loadFromExtension("framework", array(
    "test"     => null,
    "session"  => array(
        "storage_id" => "session.storage.mock_file"
    ),
    "profiler" => array(
        "collect" => false
    )
));

$container->loadFromExtension("web_profiler", array(
    "toolbar"             => false,
    "intercept_redirects" => false
));

$container->loadFromExtension("swiftmailer", array(
    "disable_delivery" => true
));

$container->loadFromExtension('doctrine', array(
    'dbal' => array(
        'host'     => 'localhost',
        'dbname'   => 'mautictest',
        'user'     => 'root',
        'password' => 'root',
    ),
));

$loader->import("security_test.php");