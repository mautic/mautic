<?php

$parameters = [
    'api_enabled'                           => true,
    'api_enable_basic_auth'                 => true,
    'db_driver'                             => 'pdo_mysql',
    'db_host'                               => '127.0.0.1',
    'db_table_prefix'                       => null,
    'db_port'                               => getenv('DB_PORT'),
    'db_name'                               => 'mautictest',
    'db_user'                               => 'root',
    'db_password'                           => '',
    'admin_email'                           => 'github-actions@mautic.org',
    'admin_password'                        => 'GitHubActionsIsAwesome',
    'mailer_dsn'                            => 'smtp://localhost:25',
    'messenger_dsn'                         => 'in-memory://',
];
