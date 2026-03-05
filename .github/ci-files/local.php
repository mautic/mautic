<?php

$parameters = [
    'api_enabled'           => true,
    'api_enable_basic_auth' => true,
    'db_driver'             => getenv('DB_DRIVER') ?: 'pdo_mysql',
    'db_charset'            => getenv('DB_CHARSET') ?: 'utf8mb4',
    'db_host'               => getenv('DB_HOST') ?: '127.0.0.1',
    'db_table_prefix'       => null,
    'db_port'               => getenv('DB_PORT') ?: 3306,
    'db_name'               => getenv('DB_NAME') ?: 'mautictest',
    'db_user'               => getenv('DB_USER') ?: 'root',
    'db_password'           => getenv('DB_PASSWD') ?: '',
    'admin_email'           => 'github-actions@mautic.org',
    'admin_password'        => 'GitHubActionsIsAwesome',
];
